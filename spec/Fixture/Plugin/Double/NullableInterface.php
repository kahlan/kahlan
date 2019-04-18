<?php
namespace Kahlan\Spec\Fixture\Plugin\Double;

interface NullableInterface
{
    public function foo(?int $integer = null): ?int;
    public function doz(?\Kahlan\Spec\Fixture\Plugin\Double\Doz $instance = null): ?\Kahlan\Spec\Fixture\Plugin\Double\Doz;
}
