<?php
/**
 * Kahlan: PHP Testing Framework
 *
 * @copyright     Copyright 2013, CrysaLEAD
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace kahlan\jit\patcher;

class Pointcut {

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
    public static $prefix = 'KPOINTCUT';

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
     * Helper for `Pointcut::process()`.
     *
     * @param array $nodes A node array to patch.
     */
    protected function _processTree($nodes) {
        foreach ($nodes as $node) {
            if ($node->type === 'class') {
                $this->_processMethods($node->tree);
            } elseif (count($node->tree)) {
                $this->_processTree($node->tree);
            }
        }
    }

    /**
     * Helper for `Pointcut::process()`.
     *
     * @param NodeDef The node to patch.
     */
    protected function _processMethods($node) {
        foreach ($node as $child) {
            if (!$child->processable) {
                continue;
            }
            $process = (
                $child->type === 'function' &&
                $child->isMethod &&
                !isset($child->visibility['abstract'])
            );
            if ($process) {
                $code = $this->_classes['node'];
                $before = new $code($this->_before(), 'code');
                $before->parent = $child;
                $before->processable = false;
                $before->namespace = $child->namespace;
                array_unshift($child->tree, $before);
            }
        }
    }

    /**
     * Before closure pattern.
     *
     * @return string.
     */
    protected function _before() {
        $prefix = static::$prefix;
        return "\$__{$prefix}_ARGS__ = func_get_args(); \$__{$prefix}_SELF__ = isset(\$this) ? \$this : get_called_class(); if (\$__{$prefix}__ = \kahlan\plugin\Pointcut::before(__METHOD__, \$__{$prefix}_SELF__, \$__{$prefix}_ARGS__)) { return \$__{$prefix}__(\$__{$prefix}_SELF__, \$__{$prefix}_ARGS__); }";
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
    public static function processBacktrace($options, $backtrace) {
        return $backtrace;
    }
}

?>