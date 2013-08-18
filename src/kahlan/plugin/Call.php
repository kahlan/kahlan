<?php
/**
 * Kahlan: PHP Testing Framework
 *
 * @copyright     Copyright 2013, CrysaLEAD & Craftsmen
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace kahlan\plugin;

use InvalidArgumentException;
use kahlan\util\String;
use kahlan\plugin\call\Message;

class Call {

	/**
	 * Registered instance/class to log
	 *
	 * @var array
	 */
	protected static $_registered = [];

	/**
	 * Logged calls
	 *
	 * @var array
	 */
	protected static $_logs = [];

	/**
	 * Current index of logged calls per reference
	 *
	 * @var array
	 */
	protected static $_index = 0;

	/**
	 * Message invocation
	 *
	 * @var array
	 */
	protected $_message = [];

	/**
	 * Reference
	 *
	 * @var array
	 */
	protected $_reference = null;

	/**
	 * Static call
	 *
	 * @var boolean
	 */
	protected $_static = false;

	/**
	 * Constructor
	 *
	 * @param mixed $reference An instance or a fully namespaced class name.
	 */
	public function __construct($reference, $static = false) {
		$this->_reference = $reference;
		$this->_static = $static;
		if (is_object($reference)) {
			static::$_registered[get_class($reference)] = $this;
		}
		static::$_registered[String::hash($reference)] = $this;
	}

	/**
	 * Active method call logging for a method
	 *
	 * @param string $name method name.
	 */
	public function method($name) {
		return $this->_message = new Message([
			'reference' => $this->_reference,
			'static' => $this->_static,
			'name' => $name
		]);
	}

	/**
	 * Log a call.
	 *
	 * @param mixed  $reference An instance or a fully namespaced class name.
	 * @param string $call      The method name.
	 */
	public static function log($reference, $call) {
		$hash = String::hash($reference);
		if (is_object($reference)) {
			$class = get_class($reference);
			if (!isset(static::$_registered[$hash]) && !isset(static::$_registered[$class])) {
				return;
			}
			$call += ['instance' => $reference, 'class' => $class, 'static' => false];
		} else {
			if (!isset(static::$_registered[$hash])) {
				return;
			}
			$call += ['instance' => null, 'class' => $reference, 'static' => true];
		}
		static::$_logs[] = $call;
	}

	/**
	 * Return Logged calls.
	 */
	public static function logs() {
		return static::$_logs;
	}

	/**
	 * Find a logged call.
	 *
	 * @param  mixed       $reference An instance or a fully namespaced class name.
	 * @param  string      $method    The method name.
	 * @param  array       $with      The required arguments.
	 * @return object|null Return the subbed method or `null` if not founded.
	 */
	public static function find($reference, $call, $reset = true) {
		if ($reset) {
			static::$_index = 0;
		}
		$index = static::$_index;
		$count = count(static::$_logs);
		for ($i = $index; $i < $count; $i++) {
			$log = static::$_logs[$i];
			if (is_object($reference)) {
				if($reference !== $log['instance']) {
					continue;
				}
			} elseif ($reference !== $log['class']) {
				continue;
			}

			if ($call->match($log)) {
				static::$_index = $i + 1;
				return true;
			}
		}
		return false;
	}

	/**
	 * Clear the registered references & logs.
	 */
	public static function clear() {
		static::$_registered = [];
		static::$_logs = [];
		static::$_index = [];
	}
}

?>