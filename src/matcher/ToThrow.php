<?php
/**
 * Kahlan: PHP Testing Framework
 *
 * @copyright     Copyright 2013, CrysaLEAD
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
		$exception = static::expected($expected);

		if (!$e = static::actual($actual)) {
			return false;
		}
		if (is_string($expected)) {
			return $e->getMessage() === $expected;
		}
		$class = get_class($exception);
		if ($class === 'kahlan\matcher\AnyException' || get_class($e) === $class) {
			$sameCode = $e->getCode() === $exception->getCode();
			$sameMessage = $e->getMessage() === $exception->getMessage();
			$sameMessage = $sameMessage || !$exception->getMessage();
			return $sameCode && $sameMessage;
		}
	}

	/**
	 * Normalize the actual value as an Exception.
	 *
	 * @param  mixed $actual The actual value to be normalized.
	 * @return mixed The normalized value.
	 */
	public static function actual($actual) {
		try {
			$actual();
		} catch (Exception $e) {
			return $e;
		}
	}

	/**
	 * Normalize the expected value as an Exception.
	 *
	 * @param  mixed $expected The expected value to be normalized.
	 * @return mixed The normalized value.
	 */
	public static function expected($expected) {
		if ($expected === null || is_string($expected)) {
			return new AnyException($expected);
		}
		return $expected;
	}

	public static function description($report) {
		$description = "throw a compatible exception.";
		$params['actual'] = static::actual($report['params']['actual']);
		$params['expected'] = static::expected($report['params']['expected']);
		return compact('description', 'params');
	}
}

?>