<?php
namespace Kahlan\Spec\Fixture\Jit\Patcher\Quit;

class Example
{
    public function exitStatement()
    {
        exit(-1);
    }

    public function dieStatement()
    {
        die();
    }

    public function normalStatement()
    {
        fooexit();
        $instance->exit();
        $options = (array) $options;
    }
}
