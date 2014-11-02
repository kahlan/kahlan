<?php
namespace kahlan\matcher;

class ToBeAnInstanceOf
{
    /**
     * Expect that `$actual` is an instance of `$expected`.
     *
     * @param  mixed   $actual The actual value.
     * @param  mixed   $expected The expected value.
     * @return boolean
     */
    public static function match($actual, $expected)
    {
        return $actual instanceof $expected;
    }

    public static function description()
    {
        return "be an instance of expected.";
    }
}
