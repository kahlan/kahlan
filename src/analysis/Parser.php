<?php
/**
 * Kahlan: PHP Testing Framework
 *
 * @copyright     Copyright 2013, CrysaLEAD
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace kahlan\analysis;

use kahlan\analysis\code\NodeDef;
use kahlan\analysis\code\ClassDef;
use kahlan\analysis\code\TraitDef;
use kahlan\analysis\code\InterfaceDef;
use kahlan\analysis\code\FunctionDef;
use kahlan\analysis\code\NamespaceDef;

/**
 * Crude parser providing some code block structure of PHP files to facilitate analysis.
 */
class Parser {

	/**
	 * The current streamer.
	 *
	 * @var object
	 */
	protected static $_stream = null;

	/**
	 * Indicate the current the current states of the parser.
	 *
	 * [
	 *    'php'        => false,  // Indicate if the parser is in a PHP block.
	 *    'open'       => false,  // Indicate if the parser parsed an open tag.
	 *    'class'      => false,  // Indicate if the parser is in a PHP class.
	 *    'lines'      => false,  // Indicate if the parser need to process line mathing.
	 *    'num'        => 0,      // Current line number.
	 *    'root'       => object, // Root node.
	 *    'current'    => object, // Current node.
	 *    'visibility' => ''      // Store function visibility.
	 *    'uses'       => [],     // Maintain the uses dependencies
	 *    'body'       => '',     // Maintain the current parsed content
	 *    'nodes'      => [],     // Maintain the nested bracket leveling
	 *    'bracket'    => 0       // Depth level of opened brackets
	 * ]
	 *
	 * @see kahlan\analysis\Parser::_resetStates()
	 * @var array
	 */
	protected static $_states = [];

	/**
	 * Unparsing a node
	 *
	 * @param  NodeDef A node definition.
	 * @return string  the unparsed file.
	 */
	public static function unparse($node) {
		return (string) $node;
	}

	/**
	 * Parsing a file into nested nodes.
	 *
	 * @param  string  A file.
	 * @param  boolean Indicate if the parser need to process line mathing.
	 * @return NodeDef the parsed file node.
	 */
	public static function parse($content, $lines = false) {
		static::$_stream = new TokenStream(['source' => $content]);
		$root = static::_resetStates($lines);
		while ($token = static::$_stream->current(true)) {
			$current = static::$_states['current'];
			switch ($token[0]) {
				case T_OPEN_TAG:
				case T_OPEN_TAG_WITH_ECHO:
					static::_codeNode();
					static::$_states['open'] = true;
					static::$_states['body'] .= $token[1];
				break;
				case T_CLOSE_TAG:
					static::_codeNode();
					static::$_states['body'] .= $token[1];
					static::_codeNode();
					static::$_states['open'] = false;
				break;
				case T_DOC_COMMENT:
				case T_COMMENT:
					static::_commentNode();
				break;
				case T_CONSTANT_ENCAPSED_STRING:
					static::_constantStringNode();
				break;
				case '"':
					static::_stringNode();
				break;
				case '}':
					static::_closeBracket();
				break;
				case T_NAMESPACE:
					static::_namespaceNode();
				break;
				case T_USE:
					static::_use();
				break;
				case T_TRAIT:
					static::_traitNode();
				break;
				case T_INTERFACE:
					static::_interfaceNode();
				break;
				case T_CLASS:
					static::_classNode();
				break;
				case T_FINAL:
				case T_ABSTRACT:
				case T_PRIVATE:
				case T_PROTECTED:
				case T_PUBLIC:
				case T_STATIC:
					if ($current->type === 'class') {
						static::$_states['visibility'] .= $token[1];
					} else {
						static::$_states['body'] .= $token[1];
					}
				break;
				case T_FUNCTION:
					static::_functionNode();
					$buffered = '';
				break;
				case T_VARIABLE:
					static::$_states['body'] .= static::$_states['visibility'];
					static::$_states['visibility'] = '';
				default:
					if (!static::$_states['visibility']) {
						static::$_states['body'] .= $token[1];
					} else {
						static::$_states['visibility'] .= $token[1];
					}
				break;
			}
			if (static::$_stream->current() === '{') {
				static::$_states['bracket']++;
			}
			static::$_stream->next();
		}
		static::_codeNode();
		static::_flushUses();
		$root->lines['start'] = 0;
		$root->lines['stop'] = static::$_states['num'] - 1;
		static::$_stream->rewind();
		return $root;
	}

	/**
	 * Manage brackets.
	 */
	protected static function _closeBracket() {
		static::$_states['bracket']--;
		if (!static::$_states['nodes'] || end(static::$_states['nodes']) < static::$_states['bracket']) {
			$token = static::$_stream->current(true);
			static::$_states['body'] .= $token[0];
			return;
		}
		array_pop(static::$_states['nodes']);

		$current = static::$_states['current'];
		static::_codeNode();
		$current->close = '}';
		if ($current->type === 'function' && $current->isClosure) {
			$current->close .= static::$_stream->next([')', ';', ',']);
		}

		static::$_states['current'] = $current->parent;
	}

	/**
	 * Manage use statement.
	 */
	protected static function _use() {
		$current = static::$_states['current'];
		$token = static::$_stream->current(true);
		if ($current->type === 'class') {
			static::$_states['body'] .= $token[1];
			return;
		}
		$last = $alias = $use = '';
		$as = false;
		while ($token[0] !== ';') {
			static::$_states['body'] .= $token[1];
			if (!$token = static::$_stream->next(true)) {
				break;
			}
			switch ($token[0]) {
				case ',':
					$as ? static::$_states['uses'][$alias] = $use : static::$_states['uses'][$last] = $use;
					$last = $alias = $use = '';
					$as = false;
				break;
				case T_STRING:
					$last = $token[1];
				case T_NS_SEPARATOR:
					$as ? $alias .= $token[1] : $use .= $token[1];
				break;
				case T_AS:
					$as = true;
				break;
			}
		}
		static::$_states['body'] .= $token[0];
		$as ? static::$_states['uses'][$alias] = $use : static::$_states['uses'][$last] = $use;
	}

	/**
	 * Build a namespace node.
	 */
	protected static function _namespaceNode() {
		static::_codeNode();
		static::_flushUses();
		$body = static::$_stream->current();
		$name = static::$_stream->next([';', '{']);
		static::$_states['body'] .= $body;
		static::$_states['namespace'] = $node = new NamespaceDef($body . $name);
		$node->name = trim(substr($name, 0, -1));
		static::$_states['nodes'] = [static::$_states['bracket']];
		static::$_states['bracket'] = 1;
		static::$_states['current'] = static::$_states['root'];
		static::$_states['current'] = static::_contextualize($node);
		$node->namespace = $node;
	}

	/**
	 * Attache the founded uses to the current namespace.
	 */
	protected static function _flushUses() {
		if ($namespace = static::$_states['namespace']) {
			$namespace->uses = static::$_states['uses'];
			static::$_states['uses'] = [];
		}
	}

	/**
	 * Build a trait node.
	 */
	protected static function _traitNode() {
		static::_codeNode();
		$body = static::$_stream->current() . static::$_stream->next([';', '{']);
		static::$_states['body'] .= $body;
		$node = new TraitDef($body);
		$node->name = substr($body, 0, -1);
		static::$_states['nodes'][] = static::$_states['bracket'];
		static::$_states['current'] = static::_contextualize($node);
	}

	/**
	 * Build an interface node.
	 */
	protected static function _interfaceNode() {
		static::_codeNode();
		$body = static::$_stream->current() . static::$_stream->next(['{']);
		static::$_states['body'] .= $body;
		$node = new InterfaceDef($body);
		$node->name = substr($body, 0, -1);
		static::$_states['nodes'][] = static::$_states['bracket'];
		static::$_states['current'] = static::_contextualize($node);
	}

	/**
	 * Build a class node.
	 */
	protected static function _classNode() {
		static::_codeNode();
		$node = new ClassDef();
		$token = static::$_stream->current(true);
		$body = $token[1];
		$body .= static::$_stream->skipWhitespaces();
		$body .= $node->name = static::$_stream->current();
		$body .= static::$_stream->next(['{', T_EXTENDS]);
		$token = static::$_stream->current(true);
		if ($token[0] === T_EXTENDS) {
			$body .= static::$_stream->skipWhitespaces();
			$body .= $node->extends = static::$_stream->skipWhile([T_STRING, T_NS_SEPARATOR]);
			if (static::$_stream->current() === '{') {
				$body .= static::$_stream->current();
			} else {
				$body .= static::$_stream->current() . static::$_stream->next('{');
			}
		}
		$node->body = $body;
		static::$_states['body'] .= $body;
		static::$_states['nodes'][] = static::$_states['bracket'];
		static::$_states['current'] = static::_contextualize($node);
	}

	/**
	 * Build a function node.
	 */
	protected static function _functionNode() {
		static::_codeNode();
		$node = new FunctionDef();
		$token = static::$_stream->current(true);
		$parent = static::$_states['current'];

		$body = $token[1];
		$name = substr(static::$_stream->next('('), 0, -1);
		$body .= $name;
		$node->name = trim($name);
		$args = static::_parseArgs();
		$node->args = $args['args'];
		$body .= $args['body'] . static::$_stream->next([';', '{']);
		$isMethod = $parent && $parent->type === 'class';
		$node->isMethod = $isMethod;
		$node->isClosure = !$node->name;
		if ($isMethod) {
			$body = static::$_states['visibility'] . $body;
			$visibility = preg_split('~\s+~', static::$_states['visibility'], null, PREG_SPLIT_NO_EMPTY);
			$node->visibility = array_fill_keys($visibility, true);
			static::$_states['visibility'] = '';
		}
		$node->body = $body;
		static::$_states['body'] .= $body;
		static::_contextualize($node);

		// Looking for brackets only if not an "abstract function"
		if (static::$_stream->current() === '{') {
			static::$_states['nodes'][] = static::$_states['bracket'];
			static::$_states['current'] = $node;
		}
	}

	/**
	 * Extracting a function/method args array from a stream.
	 *
	 * @param  TokenStream The stream.
	 * @return array The function/method args array.
	 */
	protected static function _parseArgs() {
		$inString = false;
		$cpt = 0;
		$last = $char = $value = $name = '';
		$args = [];
		$body = '';
		while ($token = static::$_stream->current(true)) {
			$body .= $token[1];
			switch ($token[0]) {
				case '"':
					$value .= static::$_stream->next('"');
				break;
				case '(':
					if ($cpt) {
						$value .= $token[1];
					}
					$cpt++;
				break;
				case '=':
					$name = $value;
					$value = '';
				break;
				case ')':
					$cpt--;
					if ($cpt) {
						$value .= $token[1];
						break;
					}
				case ',':
					$value = trim($value);
					if ($value !== '') {
						$name ? $args[trim($name)] = $value : $args[] = $value;
					}
					$name = $value = '';
				break;
				default:
					$value .= $token[1];
				break;
			}
			if ($token[1] === ')' && $cpt === 0) {
				break;
			}
			static::$_stream->next();
		}
		return compact('args', 'body');
	}

	/**
	 * Build a code node.
	 */
	protected static function _codeNode() {
		if (!static::$_states['body']) {
			return null;
		}
		if (static::$_states['open']) {
			$node = new NodeDef(static::$_states['body'], 'code');
		} else {
			$node = new NodeDef(static::$_states['body'], 'plain');
		}
		static::_contextualize($node);
	}

	/**
	 * Build a string node.
	 */
	protected static function _stringNode() {
		static::_codeNode();
		$token = static::$_stream->current(true);
		static::$_states['body'] .= $token[0] . static::$_stream->next('"');
		$node = new NodeDef(static::$_states['body'], 'string');
		static::_contextualize($node);
	}

	/**
	 * Build a string node.
	 */
	protected static function _constantStringNode() {
		static::_codeNode();
		$token = static::$_stream->current(true);
		static::$_states['body'] = $token[1];
		$node = new NodeDef(static::$_states['body'], 'string');
		static::_contextualize($node);
	}

	/**
	 * Build a comment node.
	 */
	protected static function _commentNode() {
		static::_codeNode();
		$token = static::$_stream->current(true);
		static::$_states['body'] = $token[1];
		$node = new NodeDef(static::$_states['body'], 'comment');
		static::_contextualize($node);
	}

	/**
	 * Contextualize a node.
	 */
	protected static function _contextualize($node) {
		$parent = static::$_states['current'];
		$node->namespace = $parent ? $parent->namespace : null;
		$node->parent = $parent;

		if (static::$_states['lines']) {
			static::_lines($node);
		}
		$parent->tree[] = $node;

		$node->inPhp = static::$_states['php'];
		static::$_states['php'] = static::$_states['open'];
		static::$_states['body'] = '';
		return $node;
	}

	/**
	 * Add line matching to the root node.
	 *
	 * @param  NodeDef The node to match.
	 */
	protected static function _lines($node) {
		$body = static::$_states['body'];
		if (!$body) {
			return;
		}
		$current = static::_current($node, $body);
		$size = strlen($body);

		$num = &static::$_states['num'];
		for ($i = 0; $i < $size; $i++) {
			if ($body[$i] === "\n") {
				static::$_states['root']->lines['content'][$num] = $current;
				if ($current->lines['start'] === null) {
					$current->lines['start'] = $num;
				}
				$current->lines['stop'] = $num;
				$num++;
				$current = $node;
			}
		}
		if ($current->parent) {
			$current->parent->lines['stop'] = $num;
		}
	}

	/**
	 * Returns the current node attached to a line.
	 *
	 * @see    kahlan\analysis\Parser::_line()
	 * @param  NodeDef The node to match.
	 * @param  NodeDef The parent node.
	 * @param  NodeDef The root node.
	 * @param  NodeDef
	 */
	protected static function _current($node, $body) {
		$current = null;
		$parent = static::$_states['current'];
		if (preg_match("/^\s*\n/", $body) && static::$_states['open']) {
			$current = $parent ? end($parent->tree) : $node;
		}
		if (empty($parent->tree) && $parent->type !== 'file') {
			$current = $parent;
		}
		if (!$current) {
			$current = $node;
		}
		return $current;
	}

	protected static function _resetStates($lines) {
		$root = new NodeDef('', 'file');
		static::$_states = [
			'php'        => false,
			'open'       => false,
			'namespace'  => null,
			'lines'      => $lines,
			'num'        => 0,
			'root'       => $root,
			'current'    => $root,
			'visibility' => '',
			'uses'       => [],
			'body'       => '',
			'nodes'      => [],
			'bracket'    => 0
		];
		return $root;
	}

	public static function debug($content) {
		$root = static::parse($content, true);
		$lines = preg_split("~\n~", $content);
		$result = '';
		foreach ($root->lines['content'] as $num => $node) {
			$line = $num + 1;
			$start = $node->lines['start'] + 1;
			$stop = $node->lines['stop'] + 1;
			$result .= '#' . str_pad($line, 6, ' ');
			$result .= '[' . str_pad($node->type, 9, ' ', STR_PAD_BOTH) . "]";
			$result .= ' ' . str_pad("#{$start} > #{$stop}", 16, ' ') . "|";
			$result .= $lines[$num] . "\n";
		}
		return $result;
	}
}

?>