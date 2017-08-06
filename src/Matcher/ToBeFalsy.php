<?php
namespace Kahlan\Matcher;

class ToBeFalsy extends ToEqual
{
    /**
     * Expect that `$actual` is falsy.
     *
     * @param  mixed $actual   The actual value.
     * @param  mixed $expected The expected value.
     *
     * @return bool
     */
    public static function match($actual, $expected = false)
    {
        return parent::match($actual, false);
    }

    /**
     * Returns the description message.
     *
     * @return string The description message.
     */
    public static function description()
    {
        return "be falsy.";
    }
}
