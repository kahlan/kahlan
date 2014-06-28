<?php
/**
 * Kahlan: PHP Testing Framework
 *
 * @copyright     Copyright 2013, CrysaLEAD
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace kahlan\jit\patcher;

class Quit {

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
		$alphanum = '[\\\a-zA-Z0-9_\\x7f-\\xff]';
		$regex = "/(?<!\:|\\\$|\>|{$alphanum})(\s*)((?:exit|die)\s*\()/m";

		foreach ($nodes as $node) {
			$parent = $node->parent;
			if ($node->processable && $node->type === 'code') {
				$node->body = preg_replace($regex, '\1\kahlan\plugin\Quit::quit(', $node->body);
			}
			if (count($node->tree)) {
				$this->_processTree($node->tree);
			}
		}
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