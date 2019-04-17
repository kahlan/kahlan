<?php
namespace Kahlan\Spec\Fixture\Plugin\Double;

interface NullableInterface
{
    public function foo(?int $limit = null): ?int;
}
