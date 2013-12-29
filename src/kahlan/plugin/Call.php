<?php
/**
 * Kahlan: PHP Testing Framework
 *
 * @copyright     Copyright 2013, CrysaLEAD
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
	 * @param  mixed      $reference An instance or a fully namespaced class name.
	 * @param  string     $method    The method name.
	 * @param  boolean    $reset     If `true` start finding from the start of the logs.
	 * @return array|null Return founded log call.
	 */
	public static function find($reference, $call = null, $reset = true) {
		if ($reset) {
			static::$_index = 0;
		}
		if ($call === null) {
			return static::_findAll($reference);
		}
		$index = static::$_index;
		$count = count(static::$_logs);
		for ($i = $index; $i < $count; $i++) {
			$log = static::$_logs[$i];
			if (!static::_matchReference($reference, $log)) {
				continue;
			}

			if ($call->match($log)) {
				static::$_index = $i + 1;
				return $log;
			}
		}
		return false;
	}

	protected static function _findAll($reference) {
		$result = [];
		$index = static::$_index;
		$count = count(static::$_logs);
		for ($i = $index; $i < $count; $i++) {
			$log = static::$_logs[$i];
			if (static::_matchReference($reference, $log)) {
				$result[] = $log;
			}
		}
		return $result;
	}

	protected static function _matchReference($reference, $log) {
		if (is_object($reference)) {
			if ($reference !== $log['instance']) {
				return false;
			}
		} elseif ($reference !== $log['class']) {
			return false;
		}
		return true;
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