<?php
namespace kahlan\spec\fixture\jit\patcher\quit;

class Example
{
    public function exitStatement()
    {
        \kahlan\plugin\Quit::quit(-1);
    }

    public function dieStatement()
    {
        \kahlan\plugin\Quit::quit();
    }

    public function normalStatement()
    {
        fooexit();
        $instance->exit();
        $options = (array) $options;
    }
}
