<?php $__KMONKEY__3 = \Kahlan\Plugin\Monkey::patched(null, 'name\space\MyClass2'); ?><?php $__KMONKEY__2 = \Kahlan\Plugin\Monkey::patched(null, 'name\space\MyClass'); ?><?php $__KMONKEY__1 = \Kahlan\Plugin\Monkey::patched(__NAMESPACE__ , 'mt_rand', true); ?><?php $__KMONKEY__0 = \Kahlan\Plugin\Monkey::patched(__NAMESPACE__ , 'function_exists', true); ?><?php
use name\space\MyClass as MyAlias;
use name\space as space;

if ($__KMONKEY__0('myfunction')) {
    $thatIsWeird = true;
}

$rand = $__KMONKEY__1();
new $__KMONKEY__2;
new $__KMONKEY__3();
