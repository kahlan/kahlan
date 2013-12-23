<?php
/**
 * Kahlan: PHP Testing Framework
 *
 * @copyright     Copyright 2013, CrysaLEAD
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace kahlan;

use Exception;
use kahlan\analysis\Debugger;
use kahlan\analysis\Inspector;

class Matcher {

	/**
	 * The matchers list
	 *
	 * @var array
	 */
	protected static $_matchers = [];

	/**
	 * The current parent class instance.
	 *
	 * @var object
	 */
	protected $_parent = null;

	/**
	 * The current value to test.
	 *
	 * @var mixed
	 */
	protected $_actual = null;

	/**
	 * If `true`, the result of the test will be inverted.
	 *
	 * @var boolean
	 */
	protected $_not = false;

	/**
	 * Defered expectation.
	 *
	 * @var boolean
	 */
	protected $_defered = [];

	/**
	 * Register a matcher.
	 *
	 * @param string $name The name of the matcher.
	 * @param string $class A fully-namespaced class name.
	 */
	public static function register($name, $class) {
		static::$_matchers[$name] = $class;
	}

	/**
	 * Get a registered matcher.
	 *
	 * @param  string $name The name of the matcher.
	 * @return mixed  A fully-namespaced class name or `null` if the matcher doesn't exists.
	 */
	public static function get($name) {
		return isset(static::$_matchers[$name]) ? static::$_matchers[$name] : null;
	}

	/**
	 * Check if a matcher is registered.
	 *
	 * @param  string  $name The name of the matcher.
	 * @return boolean returns `true` if the matcher exists, `false` otherwise.
	 */
	public static function exists($name) {
		return isset(static::$_matchers[$name]);
	}

	/**
	 * Unregister a matcher.
	 *
	 * @param mixed $name The name of the matcher. If name is `true` unregister all
	 *        the matchers.
	 */
	public static function unregister($name) {
		if ($name === true) {
			static::$_matchers = [];
		} else {
			unset(static::$_matchers[$name]);
		}
	}

	/**
	 * The expect statement.
	 *
	 * @param  mixed   $actual The expression to test.
	 * @param  object  The parent context class.
	 * @return boolean
	 */
	public function expect($actual, $parent) {
		$this->_not = false;
		$this->_parent = $parent;
		$this->_actual = $actual;
		return $this;
	}

	/**
	 * Call a registered matcher.
	 *
	 * @param  string  $matcher The name of the matcher.
	 * @param  array   $params The parameters to pass to the matcher.
	 * @return boolean
	 */
	public function __call($matcher, $params) {
		if (isset(static::$_matchers[$matcher])) {
			$class = static::$_matchers[$matcher];
			array_unshift($params, $this->_actual);
			$result = call_user_func_array($class . '::match', $params);
			if (is_object($result)) {
				$this->_defered[] = compact('class', 'matcher', 'params') + [
					'instance' => $result, 'not' => $this->_not
				];
				return $result;
			} else {
				$params = Inspector::parameters($class, 'match', $params);
				if (method_exists($class, 'parse')) {
					$params += ['parsed actual' => $class::parse($this->_actual)];
				}
				return $this->_result($result, compact('class', 'matcher', 'params'));
			}
		}
		throw new Exception("Error Undefined Matcher `{$matcher}`");
	}

	/**
	 * Resolve defered matchers.
	 */
	public function resolve() {
		foreach($this->_defered as $defered) {
			extract($defered);
			$this->_not = $not;
			$boolean = $instance->resolve();
			$params = Inspector::parameters($class, 'match', $params);
			$data = compact('class', 'matcher', 'params');
			if ($not ? $boolean : !$boolean) {
				$data['exception'] = $instance->backtrace();
			}
			$this->_result($boolean, $data);
		}
		$this->_defered = [];
		$this->_not = false;
	}

	/**
	 * Send the result to the callee & clear states.
	 *
	 * @param  boolean $boolean Set `true` for success and `false` for failure.
	 * @param  array   $data    Test details array.
	 * @return boolean
	 */
	protected function _result($boolean, $data = []) {
		$actual = $this->_actual;
		$not = $this->_not;
		$pass = $not ? !$boolean : $boolean;
		$type = $pass ? 'pass' : 'fail';
		if (!$pass) {
			$data += ['exception' => Debugger::backtrace(['start' => 3])];
		}
		$this->_parent->$type($data + compact('not', 'actual'));
		return $boolean;
	}

	/**
	 * Magic getter, if called with `'not'` invert the `_not` attribute.
	 *
	 * @param string
	 */
	public function __get($name) {
		if ($name === 'not') {
			$this->_not = !$this->_not;
			return $this;
		}
	}

	/**
	 * Reset the class.
	 */
	public static function reset() {
		static::$_matchers = [];
	}
}

?>