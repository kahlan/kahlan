<?php
namespace kahlan\spec\fixture\jit\patcher\monkey;

use kahlan\util\Text;

trait Filterable
{
    protected function dump()
    {$__KMONKEY__0 = \kahlan\plugin\Monkey::patched(null, 'kahlan\util\Text');
        return $__KMONKEY__0::dump('Hello');
    }

}
