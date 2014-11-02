<?php
namespace kahlan\matcher;

class ToBeFalsy extends ToEqual
{
    /**
     * Expect that `$actual` is falsy.
     *
     * @param  mixed   $actual The actual value.
     * @return boolean
     */
    public static function match($actual, $expected = false)
    {
        return parent::match($actual, false);
    }

    public static function description()
    {
        return "be falsy.";
    }
}
