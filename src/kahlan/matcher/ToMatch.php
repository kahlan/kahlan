<?php
/**
 * Kahlan: PHP Testing Framework
 *
 * @copyright     Copyright 2013, CrysaLEAD
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace kahlan\matcher;

class ToMatch extends ToEqual {

	/**
	 * Expect that `$actual` match the `$expected` pattern.
	 *
	 * @param  mixed   $actual The actual value.
	 * @param  mixed   $expected The expected pattern.
	 * @return boolean
	 */
	public static function match(
		$actual,
		$expected)
	{
		$actual = static::_nl($actual);
		return !!preg_match($expected, $actual);
	}

	public static function description() {
		return "match expected.";
	}
}

?>