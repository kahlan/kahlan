<?php
namespace kahlan\matcher;

class ToMatchEcho extends ToEcho
{
    /**
     * Checks that `$actual` echo the `$expected` regexp.
     *
     * @param  Closure $actual   The closure to run.
     * @param  mixed   $expected The expected string.
     * @return boolean
     */
    public static function match($actual, $expected = null)
    {
        $a = static::actual($actual);
        static::_buildDescription($a, $expected);

        if (is_callable($expected)) {
            return $expected($a);
        }

        return !!preg_match($expected, $a);
    }

    /**
     * Build the description of the runned `::match()` call.
     *
     * @param string $actual   The actual string.
     * @param string $expected The expected string.
     */
    public static function _buildDescription($actual, $expected)
    {
        $description        = "matches expected regex in echoed string.";
        $params['actual']   = $actual;
        $params['expected'] = $expected;

        static::$_description = compact('description', 'params');
    }

}
