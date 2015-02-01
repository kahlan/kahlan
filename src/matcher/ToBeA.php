<?php
namespace kahlan\matcher;

class ToBeA
{
    /**
     * Checks that `$actual` has the `$expected` type.
     *
     * @param  mixed   $actual   The actual value.
     * @param  mixed   $expected The expected value.
     * @return boolean
     */
    public static function match($actual, $expected)
    {
        return static::actual($actual) === static::expected($expected);
    }

    /**
     * Normalises the actual value in the expected format.
     *
     * @param  mixed $actual The actual value to be normalized.
     * @return mixed         The normalized value.
     */
    public static function actual($actual)
    {
        return strtolower(gettype($actual));
    }

    /**
     * Normalises the expected value.
     *
     * @param  mixed $expected The expected value to be normalized.
     * @return mixed           The normalized value.
     */
    public static function expected($expected)
    {
        if ($expected === 'bool') {
            $expected = 'boolean';
        }
        if ($expected === 'int') {
            $expected = 'integer';
        }
        if ($expected === 'float') {
            $expected = 'double';
        }
        return strtolower($expected);
    }

    /**
     * Returns the description report.
     *
     * @return array The description report.
     */
    public static function description($report)
    {
        $description = "have the expected type.";
        $params['actual'] = static::actual($report['params']['actual']);
        $params['expected'] = static::expected($report['params']['expected']);
        return compact('description', 'params');
    }

}
