<?php
namespace kahlan\matcher;

class ToBeCloseTo
{
    /**
     * Description reference of the last `::match()` call.
     *
     * @var array
     */
    public static $_description = [];

    /**
     * Checks that `$actual` is close enough to `$expected`.
     *
     * @param  mixed   $actual    The actual value.
     * @param  mixed   $expected  The expected value.
     * @param  integer $precision The precision to use.
     * @return boolean
     */
    public static function match($actual, $expected, $precision = 2)
    {
        static::_buildDescription($actual, $expected, $precision);

        if (!is_numeric($actual) || !is_numeric($expected)) {
            return false;
        }
        return abs($expected - $actual) < (pow(10, -$precision) / 2);
    }

    /**
     * Build the description of the runned `::match()` call.
     *
     * @param mixed   $actual    The actual value.
     * @param mixed   $expected  The expected value.
     * @param integer $precision The precision to use.
     */
    public static function _buildDescription($actual, $expected, $precision)
    {
        $description = "be close to expected relying to a precision of {$precision}.";
        $params['actual'] = $actual;
        $params['expected'] = $expected;
        $params['gap is >='] = pow(10, -$precision) / 2;
        static::$_description = compact('description', 'params');
    }

    /**
     * Returns the description report.
     */
    public static function description()
    {
        return static::$_description;
    }

}
