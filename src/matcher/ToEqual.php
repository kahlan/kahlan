<?php
/**
 * Kahlan: PHP Testing Framework
 *
 * @copyright     Copyright 2013, CrysaLEAD
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace kahlan\matcher;

class ToEqual
{
    /**
     * Expect that `$actual` is equal to `$expected`.
     *
     * @param  mixed   $actual The actual value.
     * @param  mixed   $expected The expected value.
     * @return boolean
     */
    public static function match($actual, $expected)
    {
        $actual = static::_nl($actual);
        $expected = static::_nl($expected);
        return $actual == $expected;
    }

    /**
     * Make EOL consistent for strings.
     *
     * @param  mixed $actual A value.
     * @return mixed A modified string or the unmodified value if it's not a string.
     */
    protected static function _nl($actual)
    {
        if (!is_string($actual)) {
            return $actual;
        }
        return preg_replace('/\r\n/', "\n", $actual);
    }

    public static function description()
    {
        return "be equal to expected (==).";
    }
}
