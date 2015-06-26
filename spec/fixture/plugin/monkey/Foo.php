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

    public function stamp($condition1 = 'condition1', $condition2 = 'condition2', $condition3 = 'condition3')
    {
      if ($condition1 == 'condition1' or ($condition2 == 'condition2' and $condition3 == 'condition2')) {
        return date('Y-m-d H:i:s', time());
      } else {
        return date('Y', time());
      }
    }
}
