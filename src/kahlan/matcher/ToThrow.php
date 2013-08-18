<?php
/**
 * Kahlan: PHP Testing Framework
 *
 * @copyright     Copyright 2013, CrysaLEAD & Craftsmen
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace kahlan\matcher;

use Exception;

class ToThrow {

	/**
	 * Expect that `$actual` throws the `$expected` exception.
	 *
	 * The value passed to `$expected` is either an exception or the expected exception's message.
	 *
	 * @param  Closure $actual The closure to run.
	 * @param  mixed   $expected A string indicating what the error text is expected to be or a
	 *                 exception instance.
	 * @return boolean
	 */
	public static function match($actual, $expected = null) {
		$exception = $expected;
		if ($expected === null || is_string($expected)) {
			$exception = new Exception($expected);
		}

		try {
			$actual();
		} catch (Exception $e) {
			if (is_string($expected)) {
				return $e->getMessage() === $expected;
			}
			$class = get_class($exception);
			if ($e instanceof $class) {
				$sameCode = $e->getCode() === $exception->getCode();
				$sameMessage = $e->getMessage() === $exception->getMessage();
				$sameMessage = $sameMessage || !$exception->getMessage();
				return $sameCode && $sameMessage;
			}
		}
		return false;
	}

	public static function description() {
		return "throw a compatible exception.";
	}
}

?>