<?php
namespace Kahlan\Jit\Patcher;


/**
 * Patcher for functions isolation from old legacy code.
 * It removes everything except "use" statements and functions.
 */
class Isolator
{
    /**
     * The JIT find file patcher.
     *
     * @param object $loader   The autoloader instance.
     * @param string $class    The fully-namespaced class name.
     * @param string $file     The corresponding found file path.
     *
     * @return string         The patched file path.
     */
    public function findFile($loader, $class, $file)
    {
        return $file;
    }

    /**
     * The JIT patchable checker.
     *
     * @param string $class   The fully-namespaced class name to check.
     *
     * @return boolean
     */
    public function patchable($class)
    {
        return true;
    }

    /**
     * The JIT patcher.
     *
     * @param object $node The node instance to patch.
     * @param string $path The file path of the source code.
     *
     * @return object       The patched node.
     */
    public function process($node, $path = null)
    {
        $this->_processTree($node);
        return $node;
    }

    /**
     * Helper for `Isolator::process()`.
     *
     * @param object $parent   The node instance tor process.
     */
    protected function _processTree($parent)
    {
        foreach ($parent->tree as $node) {
            if ($node->processable
                && !in_array($node->type, ['open', 'use', 'function'])
            ) {
                $node->body = '';
                $node->close = '';
                $node->tree = [];
            }
        }
    }
}
