<?php
namespace Kahlan\Spec\Fixture\Plugin\Double;

interface UnionTypesInterface
{
    public function foo(string|int|\DateTime|null $integer = null): string|int|\DateTime;
}
