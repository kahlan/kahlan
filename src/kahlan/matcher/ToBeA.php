<?php
/**
 * Kahlan: PHP Testing Framework
 *
 * @copyright     Copyright 2013, CrysaLEAD
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace kahlan\matcher;

class ToBeA {

	/**
	 * Expect that `$actual` has the `$expected` type.
	 *
	 * @param  mixed   $actual The actual value.
	 * @param  mixed   $expected The expected value.
	 * @return boolean
	 */
	public static function match($actual, $expected) {
		if ($expected === 'bool') {
			$expected = 'boolean';
		}
		if ($expected === 'int') {
			$expected = 'integer';
		}
		if ($expected === 'float') {
			$expected = 'double';
		}
		return static::parse($actual) === strtolower($expected);
	}

	/**
	 * Parse the actual value in the expected format.
	 *
	 * @param  mixed $actual The actual value to be parsed.
	 * @return mixed The parsed value.
	 */
	public static function parse($actual) {
		return strtolower(gettype($actual));
	}

	public static function description() {
		return "have the expected type.";
	}
}

?>