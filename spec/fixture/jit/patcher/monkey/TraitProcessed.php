<?php
namespace kahlan\spec\fixture\jit\patcher\monkey;

use string\String;

trait Filterable
{
    protected function dump()
    {$__KMONKEY__0 = \kahlan\plugin\Monkey::patched(null, 'string\String');
        return $__KMONKEY__0::dump('Hello');
    }

}
