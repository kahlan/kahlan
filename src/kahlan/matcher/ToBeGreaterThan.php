<?php
/**
 * Kahlan: PHP Testing Framework
 *
 * @copyright     Copyright 2013, CrysaLEAD
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace kahlan\matcher;

class ToBeGreaterThan {

	/**
	 * Expect that `$actual` is greater than `$expected`.
	 *
	 * @param  mixed   $actual The actual value.
	 * @param  mixed   $expected The expected value.
	 * @return boolean
	 */
	public static function match($actual, $expected) {
		return $actual > $expected;
	}

	public static function description() {
		return "be greater than expected.";
	}
}

?>