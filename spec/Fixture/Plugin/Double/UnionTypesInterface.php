<?php
namespace Kahlan\Spec\Fixture\Plugin\Double;

interface UnionTypesInterface
{
    public function foo(string|int|\DateTime $integer = null): string|int|\DateTime;
}
