<?php
namespace Kahlan\Jit\Patcher;

class FinalClass
{
    protected $_sibling;

    /**
     * The JIT find file patcher.
     *
     * @param  object $loader The autloader instance.
     * @param  string $class  The fully-namespaced class name.
     * @param  string $file   The correponding finded file path.
     * @return string         The patched file path.
     */
    public function findFile($loader, $class, $file)
    {
        return $file;
    }

    /**
     * The JIT patchable checker.
     *
     * @param  string  $class The fully-namespaced class name to check.
     * @return boolean
     */
    public function patchable($class)
    {
        return true;
    }

    /**
     * The JIT patcher.
     *
     * @param  object $node The node instance to patch.
     * @param  string $path The file path of the source code.
     * @return object       The patched node.
     */
    public function process($node, $path = null)
    {
        $this->_processTree($node);
        return $node;
    }

    /**
     * Helper for `Pointcut::process()`.
     *
     * @param array $parent The node instance tor process.
     */
    protected function _processTree($parent)
    {
        foreach ($parent->tree as $node) {
            if ($node->type === 'class' && $node->final) {
                $this->_sibling->body = preg_replace('/final\s+$/', '', $this->_sibling->body);
            } elseif (!empty($node->tree)) {
                $this->_processTree($node);
            }
            $this->_sibling = $node;
        }
    }
}
