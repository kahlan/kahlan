<?php
namespace kahlan\spec\fixture\jit\patcher\reabase;

class Example
{
    public function load()
    {
        require '/the/original/path/Rebase.php';
    }

    public function filename()
    {
        return basename('/the/original/path/Rebase.php');
    }

    public function path()
    {
        return '/the/original/path';
    }
}
