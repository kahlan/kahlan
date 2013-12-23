<?php
/**
 * Kahlan: PHP Testing Framework
 *
 * @copyright     Copyright 2013, CrysaLEAD
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace kahlan\jit\patcher;

class Monkey {

	/**
	 * Class dependencies.
	 *
	 * @var array
	 */
	protected $_classes = [
		'node' => 'kahlan\analysis\code\NodeDef',
	];

	/**
	 * Prefix to use for custom variable name
	 *
	 * @var string
	 */
	public static $prefix = 'KMONKEY';

	/**
	 * Counter for building unique variable name.
	 *
	 * @var integer
	 */
	protected $_counter = 0;

	/**
	 * Ignoring the following statements which are not valid function or class names.
	 *
	 * @var array
	 */
	protected $_blacklist = [
		'__halt_compiler' => true,
		'array' => true,
		'catch' => true,
		'compact' => true,
		'declare' => true,
		'die' => true,
		'echo' => true,
		'elseif' => true,
		'empty' => true,
		'eval' => true,
		'exit' => true,
		'extract' => true,
		'for' => true,
		'foreach' => true,
		'function' => true,
		'if' => true,
		'include' => true,
		'include_once' => true,
		'isset' => true,
		'list' => true,
		'parent' => true,
		'print' => true,
		'require' => true,
		'require_once' => true,
		'return' => true,
		'self' => true,
		'static' => true,
		'switch' => true,
		'unset' => true,
		'while' => true
	];

	/**
	 * Uses for the parsed node's namespace.
	 *
	 * @var array
	 */
	protected $_uses = [];

	/**
	 * Variables for the parsed node.
	 *
	 * @var array
	 */
	protected $_variables = [];

	/**
	 * The JIT find file patcher.
	 *
	 * @param  object $loader The autloader instance.
	 * @param  string $class  The fully-namespaced class name.
	 * @param  string $file   The correponding finded file path.
	 * @return string The patched file path.
	 */
	public function findFile($loader, $class, $file) {
		return $file;
	}

	/**
	 * The JIT patcher.
	 *
	 * @param  NodeDef The node to patch.
	 * @return NodeDef The patched node.
	 */
	public function process($node) {
		$this->_processTree($node->tree);
		return $node;
	}

	/**
	 * Helper for `Monkey::process()`.
	 *
	 * @param array $nodes A node array to patch.
	 */
	protected function _processTree($nodes) {
		$alpha = '[\\\a-zA-Z_\\x7f-\\xff]';
		$alphanum = '[\\\a-zA-Z0-9_\\x7f-\\xff]';
		$regex = "/(new\s+)?(?<!\:|\\\$|\>|{$alphanum})(\s*)({$alpha}{$alphanum}+)(\s*)(\(|;|::)/m";

		foreach ($nodes as $node) {
			$this->_variables = [];
			$parent = $node->parent;
			if ($node->processable && $node->type === 'code' && $parent->type !== 'namespace') {
				$this->_uses = $node->namespace ? $node->namespace->uses : [];
				$node->body = preg_replace_callback($regex, [$this, '_patchNode'], $node->body);
				$code = $this->_classes['node'];
				$patch = new $code(join('', $this->_variables), 'code');
				$patch->parent = $node->parent;
				$patch->namespace = $node->namespace;
				array_unshift($node->parent->tree, $patch);
			}
			if (count($node->tree)) {
				$this->_processTree($node->tree);
			}
		}
	}

	/**
	 * Helper for `Monkey::_processTree()`.
	 *
	 * @param array $matches An array of calls to patch.
	 */
	protected function _patchNode($matches) {
		$name = $matches[3];
		if (isset($this->_blacklist[$name]) || (!$matches[1] && $matches[5] !== '(' && $matches[5] !== '::')) {
				return $matches[0];
		}

		$variable = '$__' . static::$prefix . '__' . $this->_counter++;
		if ($name[0] === '\\') {
			$name = substr($name, 1);
			$args = "null , '{$name}'";
		} elseif (isset($this->_uses[$name])) {
			$args = "null, '" . $this->_uses[$name] . "'";
		} else {
			$isFunc = $matches[1] || $matches[5] === '::' ? 'false' : 'true';
			$args = "__NAMESPACE__ , '{$name}', {$isFunc}";
		}
		$this->_variables[] = $variable . " = \kahlan\plugin\Monkey::patched({$args});";
		return $matches[1] . $matches[2] . $variable . $matches[4] . $matches[5];
	}

	/**
	 * The JIT backtrace patcher (make backtrace ignore inserted closure).
	 *
	 * @see kahlan\analysis\Debugger::normalize()
	 *
	 * @param  array $options   Format for outputting stack trace.
	 * @param  array $backtrace The backtrace array.
	 * @return array The patched backtrace.
	 */
	public function processBacktrace($options, $backtrace) {
		return $backtrace;
	}
}

?>