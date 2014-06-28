<?php
/**
 * Kahlan: PHP Testing Framework
 *
 * @copyright     Copyright 2013, CrysaLEAD
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace kahlan\matcher;

class ToBeTruthy extends ToEqual
{
    /**
     * Expect that `$actual` is truthy.
     *
     * @param  mixed   $actual The actual value.
     * @return boolean
     */
    public static function match($actual, $expected = true)
    {
        return parent::match($actual, true);
    }

    public static function description()
    {
        return "be truthy.";
    }
}
