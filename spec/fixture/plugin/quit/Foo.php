<?php
namespace kahlan\spec\fixture\plugin\quit;

class Foo
{
    public function exitStatement($status = 0)
    {
        exit($status);
    }

    public function dieStatement($status = 0)
    {
        die($status);
    }
}
