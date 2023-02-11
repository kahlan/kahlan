<?php
namespace Kahlan\Spec\Fixture\Plugin\Pointcut;

class FooPhp81
{
    public function noop(): never
    {
        exit(0);
    }
}
