<?php $__KMONKEY__1 = \kahlan\plugin\Monkey::patched(__NAMESPACE__ , 'mt_rand', true); ?><?php $__KMONKEY__0 = \kahlan\plugin\Monkey::patched(__NAMESPACE__ , 'function_exists', true); ?><?php

if ($__KMONKEY__0('myfunction')) {
    $thatIsWeird = true;
}

$rand = $__KMONKEY__1();
