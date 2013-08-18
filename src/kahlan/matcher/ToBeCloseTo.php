<?php
/**
 * Kahlan: PHP Testing Framework
 *
 * @copyright     Copyright 2013, CrysaLEAD & Craftsmen
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

	public static function description() {
		return "be close to expected relying to the precision.";
	}
}

?>