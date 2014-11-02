<?php
namespace kahlan\matcher;

class ToBeGreaterThan
{
    /**
     * Expect that `$actual` is greater than `$expected`.
     *
     * @param  mixed   $actual The actual value.
     * @param  mixed   $expected The expected value.
     * @return boolean
     */
    public static function match($actual, $expected)
    {
        return $actual > $expected;
    }

    public static function description()
    {
        return "be greater than expected.";
    }
}
