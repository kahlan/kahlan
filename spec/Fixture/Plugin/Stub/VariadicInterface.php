<?php
namespace Kahlan\Spec\Fixture\Plugin\Stub;

interface VariadicInterface
{
    public function foo(int ...$integers) : int;
}
