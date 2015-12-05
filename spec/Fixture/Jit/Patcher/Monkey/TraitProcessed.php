<?php
namespace Kahlan\Spec\Fixture\Jit\Patcher\Monkey;

use Kahlan\Util\Text;

trait Filterable
{
    protected function dump()
    {$__KMONKEY__0 = \Kahlan\Plugin\Monkey::patched(null, 'Kahlan\Util\Text');
        return $__KMONKEY__0::dump('Hello');
    }

}
