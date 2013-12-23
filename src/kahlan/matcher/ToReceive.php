<?php
/**
 * Kahlan: PHP Testing Framework
 *
 * @copyright     Copyright 2013, CrysaLEAD
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace kahlan\matcher;

use kahlan\analysis\Debugger;

class ToReceive {

	/**
	 * Class dependencies.
	 *
	 * @var array
	 */
	protected $_classes = [
		'call' => 'kahlan\plugin\Call'
	];

	protected $_actual = null;

	protected $_expected = null;

	protected $_backtrace = null;

	public function __construct($actual, $expected) {
		$static = false;
		if (preg_match('/^::.*/', $expected)) {
			$static = true;
			$actual = is_object($actual) ? get_class($actual) : $actual;
			$expected = substr($expected, 2);
		}
		$this->_actual = $actual;
		$this->_expected = $expected;
		$call = $this->_classes['call'];
		$this->_call = new $call($actual, $static);
		$this->_message = $this->_call->method($expected);
		$this->_backtrace = Debugger::backtrace(['start' => 4]);
	}

	public function __call($method, $params) {
		return call_user_func_array([$this->_message, $method], $params);
	}

	public function resolve() {
		$call = $this->_classes['call'];
		return $call::find($this->_actual, $this->_message);
	}

	public function backtrace() {
		return $this->_backtrace;
	}

	/**
	 * Expect that `$actual` receive the `$expected` message.
	 *
	 * @param  mixed   $actual The actual value.
	 * @param  mixed   $expected The expected message.
	 * @return boolean
	 */
	public static function match($actual, $expected) {
		$class = get_called_class();
		return new static($actual, $expected);
	}

	public static function description() {
		return "receive a message.";
	}
}

?>