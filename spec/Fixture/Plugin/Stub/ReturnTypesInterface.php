<?php
namespace Kahlan\Spec\Fixture\Plugin\Stub;

interface ReturnTypesInterface
{
    public function foo(array $a) : bool;
    public function bar() : \Kahlan\Spec\Fixture\Reporter\Coverage\ImplementsCoverageInterface;
}
