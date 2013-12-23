<?php
/**
 * Kahlan: PHP Testing Framework
 *
 * @copyright     Copyright 2013, CrysaLEAD
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace kahlan\matcher;

use Countable;

class ToHaveLength {

	/**
	 * Parse the actual value in the expected format.
	 *
	 * @param  mixed $actual The actual value to be parsed.
	 * @return mixed The parsed value.
	 */
	public static function parse($actual) {
		if (is_string($actual)) {
			return strlen($actual);
		} elseif (is_array($actual) || $actual instanceof Countable) {
			return count($actual);
		}
	}

	/**
	 * Expect that `$actual` has the `$expected` length.
	 *
	 * @param  mixed   $actual The actual value.
	 * @param  mixed   $expected The expected value.
	 * @return boolean
	 */
	public static function match($actual, $expected) {
		return static::parse($actual) === $expected;
	}

	public static function description() {
		return "have the expected length.";
	}
}

?>