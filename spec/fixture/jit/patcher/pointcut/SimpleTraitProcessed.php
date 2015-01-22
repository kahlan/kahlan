<?php
namespace kahlan\spec\fixture\jit\patcher\pointcut;

use kahlan\MongoId;

trait SimpleTrait
{
    protected $_variable = true;

    public function __construct($options)
    {$__KPOINTCUT_ARGS__ = func_get_args(); $__KPOINTCUT_SELF__ = isset($this) ? $this : get_called_class(); if ($__KPOINTCUT__ = \kahlan\plugin\Pointcut::before(__METHOD__, $__KPOINTCUT_SELF__, $__KPOINTCUT_ARGS__)) { $r = $__KPOINTCUT__($__KPOINTCUT_SELF__, $__KPOINTCUT_ARGS__); return $r; }
    }

    function classicMethod($param1, &$param2, &$param2 = [])
    {$__KPOINTCUT_ARGS__ = func_get_args(); $__KPOINTCUT_SELF__ = isset($this) ? $this : get_called_class(); if ($__KPOINTCUT__ = \kahlan\plugin\Pointcut::before(__METHOD__, $__KPOINTCUT_SELF__, $__KPOINTCUT_ARGS__)) { $r = $__KPOINTCUT__($__KPOINTCUT_SELF__, $__KPOINTCUT_ARGS__); return $r; }
        rand(2, 5);
    }

    public function publicMethod($param1, &$param2, &$param2 = [])
    {$__KPOINTCUT_ARGS__ = func_get_args(); $__KPOINTCUT_SELF__ = isset($this) ? $this : get_called_class(); if ($__KPOINTCUT__ = \kahlan\plugin\Pointcut::before(__METHOD__, $__KPOINTCUT_SELF__, $__KPOINTCUT_ARGS__)) { $r = $__KPOINTCUT__($__KPOINTCUT_SELF__, $__KPOINTCUT_ARGS__); return $r; }
        rand(2, 5);
    }

    protected function protectedMethod($param1, &$param2, &$param2 = [])
    {$__KPOINTCUT_ARGS__ = func_get_args(); $__KPOINTCUT_SELF__ = isset($this) ? $this : get_called_class(); if ($__KPOINTCUT__ = \kahlan\plugin\Pointcut::before(__METHOD__, $__KPOINTCUT_SELF__, $__KPOINTCUT_ARGS__)) { $r = $__KPOINTCUT__($__KPOINTCUT_SELF__, $__KPOINTCUT_ARGS__); return $r; }
        rand(2, 5);
    }

    private function privateMethod($param1, &$param2, &$param2 = [])
    {$__KPOINTCUT_ARGS__ = func_get_args(); $__KPOINTCUT_SELF__ = isset($this) ? $this : get_called_class(); if ($__KPOINTCUT__ = \kahlan\plugin\Pointcut::before(__METHOD__, $__KPOINTCUT_SELF__, $__KPOINTCUT_ARGS__)) { $r = $__KPOINTCUT__($__KPOINTCUT_SELF__, $__KPOINTCUT_ARGS__); return $r; }
        rand(2, 5);
    }

    final public function finalMethod($param1 = 'default', $param2 = null)
    {$__KPOINTCUT_ARGS__ = func_get_args(); $__KPOINTCUT_SELF__ = isset($this) ? $this : get_called_class(); if ($__KPOINTCUT__ = \kahlan\plugin\Pointcut::before(__METHOD__, $__KPOINTCUT_SELF__, $__KPOINTCUT_ARGS__)) { $r = $__KPOINTCUT__($__KPOINTCUT_SELF__, $__KPOINTCUT_ARGS__); return $r; }
        rand(2, 5);
    }

    abstract public function abstractMethod($param1, &$param2 = array());

}
