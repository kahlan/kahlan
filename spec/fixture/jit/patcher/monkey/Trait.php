<?php
namespace spec\fixture\jit\patcher\monkey;

use string\String;

trait Filterable
{
    protected function dump()
    {
        return String::dump('Hello');
    }

}
