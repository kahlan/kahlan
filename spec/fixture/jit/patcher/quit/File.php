<?php
namespace kahlan\spec\fixture\jit\patcher\quit;

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
