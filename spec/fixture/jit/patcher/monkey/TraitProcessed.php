<?php
namespace kahlan\spec\fixture\jit\patcher\monkey;

use kahlan\util\Str;

trait Filterable
{
    protected function dump()
    {$__KMONKEY__0 = \kahlan\plugin\Monkey::patched(null, 'kahlan\util\Str');
        return $__KMONKEY__0::dump('Hello');
    }

}
