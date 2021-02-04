<?php
namespace Kahlan\Spec\Mock\Plugin\Double;

interface HelloInterface
{
    public function hello(): self;

    public function hi(): static;

    public function aloha(): HelloInterface;
}
