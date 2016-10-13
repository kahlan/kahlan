<?php
namespace Kahlan\Matcher;

class ToBeOneOf
{
    /**
     * Checks that `$expected` has value of `$actual`.
     *
     * @param  mixed   $actual   The actual value.
     * @param  mixed   $expected The expected value.
     * @return boolean
     */
    public static function match($actual, $expected)
    {
        return in_array($actual, $expected);
    }

    /**
     * Returns the description message.
     *
     * @return string The description message.
     */
    public static function description()
    {
        return "be part of the expected values.";
    }
}
