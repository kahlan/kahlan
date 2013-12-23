<?php
/**
 * Kahlan: PHP Testing Framework
 *
 * @copyright     Copyright 2013, CrysaLEAD
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace kahlan;

use Exception;

class Arg {

	/**
	 * Class dependencies.
	 *
	 * @var array
	 */
	protected static $_classes = [
		'matcher' => 'kahlan\Matcher'
	];

	/**
	 * The fully namespaced matcher classname.
	 *
	 * @var string
	 */
	protected $_matcher = '';

	/**
	 * The expected params.
	 *
	 * @var array
	 */
	protected $_params = [];

	/**
	 * If `true`, the result of the test will be inverted.
	 *
	 * @var boolean
	 */
	protected $_not = false;

	public function __construct($options = []) {
		$defaults = ['not' => false, 'matcher' => '', 'params' => []];
		$options += $defaults;
		$this->_not = $options['not'];
		$this->_matcher = $options['matcher'];
		$this->_params = $options['params'];
	}

	/**
	 * Create an Argument Matcher
	 *
	 * @param  string  $name   The name of the matcher.
	 * @param  array   $params The parameters to pass to the matcher.
	 * @return boolean
	 */
	public static function __callStatic($name, $params) {
		$not = false;
		if (preg_match('/^not/', $name)) {
			$matcher = lcfirst(substr($name, 3));
			$not = true;
		} else {
			$matcher = $name;
		}
		$matchers = static::$_classes['matcher'];
		if ($matcher = $matchers::get($matcher)) {
			return new static(compact('matcher', 'not', 'params'));
		}
		throw new Exception("Error Undefined Argument Matcher `{$name}`");
	}

	/**
	 * Check if `$actual` matches the matcher.
	 *
	 * @param  string  $name The actual value.
	 * @return boolean returns `true` on success and `false` otherwise.
	 */
	public function match($actual) {
		$class = $this->_matcher;
		$params = $this->_params;
		array_unshift($params, $actual);
		$boolean = call_user_func_array($class . '::match', $params);
		return $this->_not ? !$boolean : $boolean;
	}
}

?>