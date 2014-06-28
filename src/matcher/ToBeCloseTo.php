<?php
/**
 * Kahlan: PHP Testing Framework
 *
 * @copyright     Copyright 2013, CrysaLEAD
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace kahlan\matcher;

class ToBeCloseTo {

    /**
     * Expect that `$actual` is close enough to `$expected`.
     *
     * @param  mixed   $actual The actual value.
     * @param  mixed   $expected The expected value.
     * @param  integer $precision The precision to use.
     * @return boolean
     */
    public static function match($actual, $expected, $precision = 2) {
        if (!is_numeric($actual) || !is_numeric($expected)) {
            return false;
        }
        return abs($expected - $actual) < (pow(10, -$precision) / 2);
    }

    public static function description($report) {
        $precision = $report['params']['precision'];
        $description = "be close to expected relying to a precision of {$precision}.";
        $params['actual'] = $report['params']['actual'];
        $params['expected'] = $report['params']['expected'];
        $params['gap is >='] = pow(10, -$precision) / 2;
        return compact('description', 'params');
    }
}

?>