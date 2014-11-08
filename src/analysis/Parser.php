<?php
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
class Parser
{
    /**
     * The root node.
     *
     * @var object
     */
    protected $_root = null;

    /**
     * The current streamer.
     *
     * @var object
     */
    protected $_stream = null;

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
     *    'braces'     => [],     // Maintain the nested brace leveling
     *    'brace'      => 0       // Depth level of opened braces
     * ]
     *
     * @see kahlan\analysis\Parser::_resetStates()
     * @var array
     */
    protected $_states = [];

    /**
     * The constructor function
     *
     * @param array $config The configuration array.
     */
    public function __construct($config = [])
    {
        $defaults = [
            'php'        => false,
            'open'       => false,
            'lines'      => 0,
            'num'        => 0,
            'visibility' => '',
            'uses'       => [],
            'body'       => '',
            'braces'     => [],
            'brace'      => 0
        ];
        $this->_states = $config + $defaults;
        $this->_root = $this->_states['current'] = new NodeDef('', 'file');
    }

    /**
     * Parsing a file into nested nodes.
     *
     * @param  string  A file.
     * @param  boolean Indicate if the parser need to process line mathing.
     * @return NodeDef the parsed file node.
     */
    protected function _parser($content, $lines = false)
    {
        $this->_stream = new TokenStream(['source' => $content]);

        while ($token = $this->_stream->current(true)) {
            $current = $this->_states['current'];
            switch ($token[0]) {
                case T_OPEN_TAG:
                case T_OPEN_TAG_WITH_ECHO:
                    $this->_codeNode();
                    $this->_states['open'] = true;
                    $this->_states['body'] .= $token[1];
                    $this->_codeNode('open');
                break;
                case T_CLOSE_TAG:
                    $this->_codeNode();
                    $this->_states['body'] .= $token[1];
                    $this->_codeNode('close');
                    $this->_states['open'] = false;
                break;
                case T_DOC_COMMENT:
                case T_COMMENT:
                    $this->_commentNode();
                break;
                case T_CONSTANT_ENCAPSED_STRING:
                    $this->_constantStringNode();
                break;
                case '"':
                    $this->_stringNode();
                break;
                case '}':
                    $this->_closeBrace();
                break;
                case '{':
                case ';':
                    $this->_states['body'] .= $token[1];
                    $this->_codeNode();
                    $current = $this->_states['current'];
                    if ($current->type === 'code') {
                       $this->_states['current'] = $current->parent;
                    }
                break;
                case T_NAMESPACE:
                    $this->_namespaceNode();
                break;
                case T_USE:
                    $this->_use();
                break;
                case T_TRAIT:
                    $this->_traitNode();
                break;
                case T_INTERFACE:
                    $this->_interfaceNode();
                break;
                case T_CLASS:
                    $this->_classNode();
                break;
                case T_FINAL:
                case T_ABSTRACT:
                case T_PRIVATE:
                case T_PROTECTED:
                case T_PUBLIC:
                case T_STATIC:
                    if ($current->type === 'class' || $current->type === 'trait') {
                        $this->_states['visibility'] .= $token[1];
                    } else {
                        $this->_states['body'] .= $token[1];
                    }
                break;
                case T_FUNCTION:
                    $this->_functionNode();
                    $buffered = '';
                break;
                case T_VARIABLE:
                    $this->_states['body'] .= $this->_states['visibility'];
                    $this->_states['visibility'] = '';
                default:
                    if (!$this->_states['visibility']) {
                        $this->_states['body'] .= $token[1];
                    } else {
                        $this->_states['visibility'] .= $token[1];
                    }
                break;
            }
            if ($this->_stream->current() === '{') {
                $this->_states['brace']++;
            }
            $this->_stream->next();
        }
        $this->_codeNode();
        $this->_flushUses();
        $this->_lines();
        $this->_stream->rewind();
        return $this->_root;
    }

    /**
     * Manage braces.
     */
    protected function _closeBrace()
    {
        $this->_states['brace']--;
        if (!$this->_states['braces'] || end($this->_states['braces']) < $this->_states['brace']) {
            $token = $this->_stream->current(true);
            $this->_states['body'] .= $token[0];
            return;
        }
        array_pop($this->_states['braces']);

        $current = $this->_states['current'];

        $this->_codeNode();

        $current->close = '}';
        if ($current->type === 'function') {
            if ($current->isClosure) {
                $current->close .= $this->_stream->next([')', ';', ',']);
            }
        }

        if ($this->_states['lines']) {
            $current->lines['stop'] = $this->_states['num'];
            $this->_states['num'] += substr_count($current->close, "\n");
        }

        $this->_states['current'] = $current->parent;
    }

    /**
     * Manage use statement.
     */
    protected function _use()
    {
        $current = $this->_states['current'];
        $token = $this->_stream->current(true);
        if ($current->type === 'class' || $current->type === 'trait') {
            $this->_states['body'] .= $token[1];
            return;
        }
        $last = $alias = $use = '';
        $as = false;
        while ($token[0] !== ';') {
            $this->_states['body'] .= $token[1];
            if (!$token = $this->_stream->next(true)) {
                break;
            }
            switch ($token[0]) {
                case ',':
                    $as ? $this->_states['uses'][$alias] = $use : $this->_states['uses'][$last] = $use;
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
        $this->_states['body'] .= $token[0];
        $as ? $this->_states['uses'][$alias] = $use : $this->_states['uses'][$last] = $use;
    }

    /**
     * Build a namespace node.
     */
    protected function _namespaceNode()
    {
        $this->_codeNode();
        $this->_flushUses();
        $body = $this->_stream->current();
        $name = $this->_stream->next([';', '{']);
        $this->_states['body'] .= $body;
        $node = new NamespaceDef($body . $name);
        $node->name = trim(substr($name, 0, -1));
        $this->_states['braces'] = [$this->_states['brace']];
        $this->_states['brace'] = 1;
        $this->_states['current'] = $this->_root;
        $this->_states['current'] = $this->_contextualize($node);
        return $node->namespace = $node;
    }

    /**
     * Attache the founded uses to the current namespace.
     */
    protected function _flushUses()
    {
        if ($this->_states['current'] && $this->_states['current']->namespace) {
            $this->_states['current']->namespace->uses = $this->_states['uses'];
            $this->_states['uses'] = [];
        }
    }

    /**
     * Build a trait node.
     */
    protected function _traitNode()
    {
        $this->_codeNode();
        $body = $this->_stream->current() . $this->_stream->next([';', '{']);
        $this->_states['body'] .= $body;
        $node = new TraitDef($body);
        $node->name = substr($body, 0, -1);
        $this->_states['braces'][] = $this->_states['brace'];
        return $this->_states['current'] = $this->_contextualize($node);
    }

    /**
     * Build an interface node.
     */
    protected function _interfaceNode()
    {
        $this->_codeNode();
        $body = $this->_stream->current() . $this->_stream->next(['{']);
        $this->_states['body'] .= $body;
        $node = new InterfaceDef($body);
        $node->name = substr($body, 0, -1);
        $this->_states['braces'][] = $this->_states['brace'];
        return $this->_states['current'] = $this->_contextualize($node);
    }

    /**
     * Build a class node.
     */
    protected function _classNode()
    {
        $this->_codeNode();
        $node = new ClassDef();
        $token = $this->_stream->current(true);
        $body = $token[1];
        $body .= $this->_stream->skipWhitespaces();
        $body .= $node->name = $this->_stream->current();
        $body .= $this->_stream->next(['{', T_EXTENDS]);
        $token = $this->_stream->current(true);
        if ($token[0] === T_EXTENDS) {
            $body .= $this->_stream->skipWhitespaces();
            $body .= $node->extends = $this->_stream->skipWhile([T_STRING, T_NS_SEPARATOR]);
            if ($this->_stream->current() === '{') {
                $body .= $this->_stream->current();
            } else {
                $body .= $this->_stream->current() . $this->_stream->next('{');
            }
        }
        $node->body = $body;
        $this->_states['body'] .= $body;
        $this->_states['braces'][] = $this->_states['brace'];
        return $this->_states['current'] = $this->_contextualize($node);
    }

    /**
     * Build a function node.
     */
    protected function _functionNode()
    {
        $this->_codeNode();
        $node = new FunctionDef();
        $token = $this->_stream->current(true);
        $parent = $this->_states['current'];

        $body = $token[1];
        $name = substr($this->_stream->next('('), 0, -1);
        $body .= $name;
        $node->name = trim($name);
        $args = $this->_parseArgs();
        $node->args = $args['args'];
        $body .= $args['body'] . $this->_stream->next([';', '{']);
        $isMethod = $parent && ($parent->type === 'class' || $parent->type === 'trait');
        $node->isMethod = $isMethod;
        $node->isClosure = !$node->name;
        if ($isMethod) {
            $body = $this->_states['visibility'] . $body;
            $visibility = preg_split('~\s+~', $this->_states['visibility'], null, PREG_SPLIT_NO_EMPTY);
            $node->visibility = array_fill_keys($visibility, true);
            $this->_states['visibility'] = '';
        }
        $node->body = $body;
        $this->_states['body'] .= $body;
        $this->_contextualize($node);

        // Looking for braces only if not an "abstract function"
        if ($this->_stream->current() === '{') {
            $this->_states['braces'][] = $this->_states['brace'];
            $this->_states['current'] = $node;
        }

        return $node->function = $node;
    }

    /**
     * Extracting a function/method args array from a stream.
     *
     * @param  TokenStream The stream.
     * @return array The function/method args array.
     */
    protected function _parseArgs()
    {
        $inString = false;
        $cpt = 0;
        $last = $char = $value = $name = '';
        $args = [];
        $body = '';
        while ($token = $this->_stream->current(true)) {
            $body .= $token[1];
            switch ($token[0]) {
                case '"':
                    $value .= $this->_stream->next('"');
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
            $this->_stream->next();
        }
        return compact('args', 'body');
    }

    /**
     * Build a code node.
     */
    protected function _codeNode($type = null)
    {
        if (!$this->_states['body']) {
            return null;
        }
        if ($type) {
            $node = new NodeDef($this->_states['body'], $type);
        }
        else if ($this->_states['open']) {
            $parent = $this->_states['current'];
            $node = new NodeDef($this->_states['body'], 'code');
        } else {
            $node = new NodeDef($this->_states['body'], 'plain');
        }
        return $this->_contextualize($node);
    }

    /**
     * Build a string node.
     */
    protected function _stringNode()
    {
        if ($this->_states['current']->type !== 'code') {
            $this->_states['current'] = $this->_codeNode();
        } else {
            $this->_codeNode();
        }
        $token = $this->_stream->current(true);
        $this->_states['body'] .= $token[0] . $this->_stream->next('"');
        $node = new NodeDef($this->_states['body'], 'string');
        return $this->_contextualize($node);
    }

    /**
     * Build a string node.
     */
    protected function _constantStringNode()
    {
        if ($this->_states['current']->type !== 'code') {
            $this->_states['current'] = $this->_codeNode();
        } else {
            $this->_codeNode();
        }
        $token = $this->_stream->current(true);
        $this->_states['body'] = $token[1];
        $node = new NodeDef($this->_states['body'], 'string');
        return $this->_contextualize($node);
    }

    /**
     * Build a comment node.
     */
    protected function _commentNode()
    {
        $this->_codeNode();
        $token = $this->_stream->current(true);
        $this->_states['body'] = $token[1];
        $node = new NodeDef($this->_states['body'], 'comment');
        return $this->_contextualize($node);
    }

    /**
     * Contextualize a node.
     */
    protected function _contextualize($node)
    {
        $parent = $this->_states['current'];
        $node->namespace = $parent->namespace;
        $node->function = $parent->function;
        $node->parent = $parent;
        $parent->tree[] = $node;
        $this->_assignLines($node);

        $node->inPhp = $this->_states['php'];
        $this->_states['php'] = $this->_states['open'];
        $this->_states['body'] = '';
        return $node;
    }

    /**
     * Assign the node to some lines and makes them availaible at the root node.
     *
     * @param NodeDef $node The node to match.
     * @param string  $body The  to match.
     */
    protected function _assignLines($node) {
        if (!$this->_states['lines']) {
            return;
        }

        $body = $node->body;

        if (!$body) {
            return;
        }

        $num = $this->_states['num'];
        $nb = substr_count($body, "\n");

        $ignoreStart = 0;
        if (preg_match_all('/^(\s*\n)+/', $body, $match)) {
            $ignoreStart = substr_count($match[0][0], "\n");
        }

        $ignoreEnd = 0;
        if (preg_match_all('/(\n\s*)+$/', $body, $match)) {
            $ignoreEnd = substr_count($match[0][0], "\n");
        }

        $content = &$this->_root->lines['content'];

        end($content);
        $last = key($content);

        for($i = $last + 1; $i < $num + $nb; $i++) {
            if (!isset($content[$i])) {
                $content[$i] = [];
            }
        }

        $i = $ignoreStart;
        while ($i <= $nb - $ignoreEnd) {
            $line = $num + $i;

            $content[$line][] = $node;

            if ($node->lines['start'] === null) {
                $node->lines['start'] = $line;
            }
            $node->lines['stop'] = $line;
            $node = $node;
            $i++;
        }

        if ($node->parent) {
            $node->parent->lines['stop'] = $num + $nb - ($ignoreEnd ? 1 : 0);
        }

        $this->_states['num'] += $nb;
    }

    protected function _lines() {
        if (!$this->_states['lines']) {
            return;
        }

        $root = $this->_root;
        $root->lines['start'] = 0;
        $root->lines['stop'] = $this->_states['num'] - 1;

        foreach ($root->lines['content'] as $num => $nodes) {
            foreach ($nodes as $node) {
                if ($num >= $node->lines['stop'] || $node->type === 'code') {
                    continue;
                }
                if (!in_array($node, $root->lines['content'][$node->lines['stop']], true)) {
                    array_unshift($root->lines['content'][$node->lines['stop']], $node);
                }
            }
        }
    }

    /**
     * Parsing a file into nested nodes.
     *
     * @param  string  The php string to parse.
     * @param  boolean Indicate if the parser need to process line mathing.
     * @return NodeDef the parsed file node.
     */
    public static function parse($content, $config = [])
    {
        $parser = new static($config);
        return $parser->_parser($content);
    }

    /**
     * Unparsing a node
     *
     * @param  NodeDef A node definition.
     * @return string  the unparsed file.
     */
    public static function unparse($node)
    {
        return (string) $node;
    }

    public static function debug($content)
    {
        $root = static::parse($content, ['lines' => true]);
        $lines = preg_split("~\n~", $content);
        $result = '';

        $abbr = [
            'open'      => 'open',
            'close'     => 'close',
            'namespace' => 'ns',
            'class'     => 'class',
            'interface' => 'interface',
            'trait'     => 'trait',
            'function'  => 'fct',
            'attribute' => 'attr',
            'code'      => 'c',
            'comment'   => 'doc',
            'plain'     => 'p',
            'string'    => 'str'
        ];

        foreach ($root->lines['content'] as $num => $nodes) {
            $start = $stop = $line = $num + 1;
            $result .= '#' . str_pad($line, 6, ' ');
            $types = [];
            foreach ($nodes as $node) {
                $parent = $node->parent;
                if ($parent && $node->type === 'code') {
                    $inBlock = $parent->type === 'class' || $parent->type === 'trait' || $parent->type === 'interface';
                    $types[] = $abbr[$inBlock ? 'attribute' : 'code'];
                } else {
                    $types[] = $abbr[$node->type];
                }
                $stop = max($stop, $node->lines['stop'] + 1);
            }
            $result .= '[' . str_pad(join(',', $types), 25, ' ', STR_PAD_BOTH) . "]";
            $result .= ' ' . str_pad("#{$start} > #{$stop}", 16, ' ') . "|";
            $result .= $lines[$num] . "\n";
        }
        return $result;
    }
}
