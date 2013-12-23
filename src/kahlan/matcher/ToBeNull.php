<?php
/**
 * Kahlan: PHP Testing Framework
 *
 * @copyright     Copyright 2013, CrysaLEAD
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace kahlan\matcher;

class ToBeNull extends ToBe {

	/**
	 * Expect that `$actual` is `null`.
	 *
	 * @param  mixed   $actual The actual value.
	 * @return boolean
	 */
	public static function match($actual, $expected = null) {
		return parent::match($actual, null);
	}

	public static function description() {
		return "be null.";
	}
}

?>