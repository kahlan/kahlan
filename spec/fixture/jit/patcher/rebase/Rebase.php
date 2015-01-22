<?php
namespace kahlan\spec\fixture\jit\patcher\reabase;

class Example
{
    public function load()
    {
        require __FILE__;
    }

    public function filename()
    {
        return basename(__FILE__);
    }

    public function path()
    {
        return __DIR__;
    }
}
