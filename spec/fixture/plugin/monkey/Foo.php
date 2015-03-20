<?php
namespace kahlan\spec\fixture\plugin\monkey;

use kahlan\util\Str;

function rand($min, $max) {
    return ($max - $min) / 2;
}

class Foo
{
    public function time()
    {
        return time();
    }

    public function rand($min = 0, $max = 100)
    {
        return rand($min, $max);
    }

    public function datetime($datetime = 'now')
    {
        return new \DateTime($datetime);
    }

    public function dump($value)
    {
        return Str::dump($value);
    }
}
