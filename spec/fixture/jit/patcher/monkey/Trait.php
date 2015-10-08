<?php
namespace kahlan\spec\fixture\jit\patcher\monkey;

use kahlan\util\Text;

trait Filterable
{
    protected function dump()
    {
        return Text::dump('Hello');
    }

}
