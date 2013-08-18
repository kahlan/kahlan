<?php
/**
 * Kahlan: PHP Testing Framework
 *
 * @copyright     Copyright 2013, CrysaLEAD & Craftsmen
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace kahlan\matcher;

class ToEcho {

	/**
	 * Expect that `$actual` echo the `$expected` string.
	 *
	 * @param  Closure $actual The closure to run.
	 * @param  mixed   $expected The output string.
	 * @return boolean
	 */
	public static function match($actual, $expected = null) {

		ob_start();
		$actual();
		$output = ob_get_contents();
        ob_end_clean();

		return $output === $expected;
	}

	public static function description() {
		return "echo the expected string.";
	}
}

?>