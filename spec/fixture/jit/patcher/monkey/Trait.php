<?php
namespace kahlan\spec\fixture\jit\patcher\monkey;

use kahlan\util\Str;

trait Filterable
{
    protected function dump()
    {
        return Str::dump('Hello');
    }

}
