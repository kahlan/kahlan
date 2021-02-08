<?php
namespace Kahlan\Spec\Mock\Plugin\Double;

interface HelloInterface
{
    public function returnSelf(): self;

    public function returnStatic(): static;

    public function aloha(): HelloInterface;
}
