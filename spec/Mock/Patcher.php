<?php
namespace Kahlan\Spec\Mock;

use Kahlan\Plugin\Pointcut;

class Patcher
{
    /**
     * The JIT find file patcher.
     *
     * @param  object $loader The autloader instance.
     * @param  string $class  The fully-namespaced class name.
     * @param  string $file   The correponding finded file path.
     * @return string The patched file path.
     */
    public function findFile($loader, $class, $file)
    {
        $args = func_get_args();
        $self = isset($this) ? $this : get_called_class();
        if ($pointcut = Pointcut::before(__METHOD__, $self, $args)) {
            return $pointcut($self, $args);
        }
        return $file;
    }

    /**
     * The JIT patcher.
     *
     * @param  NodeDef $node The node to patch.
     * @param  string  $path The file path of the source code.
     * @return NodeDef       The patched node.
     */
    public function process($node, $path = null)
    {
        $args = func_get_args();
        $self = isset($this) ? $this : get_called_class();
        if ($pointcut = Pointcut::before(__METHOD__, $self, $args)) {
            return $pointcut($self, $args);
        }
        return $node;
    }

}
