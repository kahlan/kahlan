<?php
namespace kahlan\matcher;

class ToBe
{
    /**
     * Expect that `$actual` is identical to `$expected`.
     *
     * @param  mixed   $actual The actual value.
     * @param  mixed   $expected The expected value.
     * @return boolean
     */
    public static function match($actual, $expected)
    {
        return $actual === $expected;
    }

    public static function description()
    {
        return "be identical to expected (===).";
    }
}
