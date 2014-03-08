<?php
/**
 * Kahlan: PHP Testing Framework
 *
 * @copyright     Copyright 2013, CrysaLEAD
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace kahlan;

use Exception;
use kahlan\SkipException;
use kahlan\analysis\Debugger;

abstract class Scope {

	/**
	 * Instances stack.
	 *
	 * @var array
	 */
	protected static $_instances = [];

	/**
	 * The root instance.
	 *
	 * @var object
	 */
	protected $_root = null;

	/**
	 * The parent instance.
	 *
	 * @var object
	 */
	protected $_parent = null;

	/**
	 * The spec message.
	 *
	 * @var string
	 */
	protected $_message = null;

	/**
	 * The spec closure.
	 *
	 * @var Closure
	 */
	protected $_closure = null;

	/**
	 * The scope's data.
	 *
	 * @var array
	 */
	protected $_data = [];

	/**
	 * The results array.
	 *
	 * @var array
	 */
	protected $_results = [
		'pass' => [], 'fail' => [], 'exception' => [], 'skip' => [], 'incomplete' => []
	];

	/**
	 * Exclusive scope detected.
	 *
	 * @var array
	 */
	protected $_exclusive = false;

	/**
	 * Getter.
	 *
	 * @param  string $key The name of the variable.
	 * @return mixed  The value of the variable.
	 */
	public function &__get($key) {
		if (array_key_exists($key, $this->_data)) {
			return $this->_data[$key];
		}
		if ($this->_parent !== null) {
			return $this->_parent->__get($key);
		}
		throw new Exception("Undefined variable `{$key}`");
	}

	/**
	 * Setter.
	 *
	 * @param  string $key   The name of the variable.
	 * @param  mixed  $value The value of the variable.
	 * @return mixed  The value of the variable.
	 */
	public function __set($key, $value) {
		return $this->_data[$key] = $value;
	}

	/**
	 * Allow closures assigned to the scope property to be inkovable.
	 *
	 * @param  string $name   Name of the method being called.
	 * @param  array  $params Enumerated array containing the passed parameters.
	 * @return mixed
	 * @throws Throw an Exception if the property doesn't exists / is not callable.
	 */
	public function __call($name, $params) {
		$property = null;
		try {
			$property = $this->__get($name);
		} catch (Exception $e) {
			throw new Exception("Undefined callable variable `{$name}`");
		}

		if (is_callable($property)) {
			return call_user_func_array($property, $params);
		}
		throw new Exception("Uncallable variable `{$name}`");
	}

	/**
	 * Return the spec's message.
	 *
	 * @return array
	 */
	public function message() {
		return $this->_message;
	}

	/**
	 * Return all messages upon the root.
	 *
	 * @return array
	 */
	public function messages() {
		$messages = [];
		$instances = $this->_parents(true);
		foreach ($instances as $instance) {
			$messages[] = $instance->message();
		}
		return $messages;
	}

	/**
	 * Skip test(s) if the condition is `true`.
	 *
	 * @param boolean $condition
	 * @throws SkipException
	 */
	public function skipIf($condition) {
		if ($condition) {
			if ($this instanceof Suite) {
				foreach ($this->_childs as $child) {
					$this->report('progress');
				}
			}
			throw new SkipException();
		}
	}

	/**
	 * Manage catched exception.
	 *
	 * @param Exception $exception The catched exception.
	 */
	protected function _exception($exception) {
		$data = compact('exception');
		switch(get_class($exception)) {
			case 'kahlan\SkipException':
				$this->skip($data);
			break;
			case 'kahlan\IncompleteException':
				$this->incomplete($data);
			break;
			default:
				$this->exception($data);
			break;
		}
	}

	/**
	 * Log a passing test.
	 *
	 * @param array $data The result data.
	 */
	public function pass($data = []) {
		$this->log('pass', $data);
	}

	/**
	 * Log a failed test.
	 *
	 * @param array $data The result data.
	 */
	public function fail($data = []) {
		$this->log('fail', $data);
	}

	/**
	 * Log an uncached exception test.
	 *
	 * @param array $data The result data.
	 */
	public function exception($data = []) {
		$this->log('exception', $data);
	}

	/**
	 * Log a skipped test.
	 *
	 * @param array $data The result data.
	 */
	public function skip($data = []) {
		$this->log('skip', $data);
	}

	/**
	 * Log a incomplete test.
	 *
	 * @param array $data The result data.
	 */
	public function incomplete($data = []) {
		$this->log('incomplete', $data);
	}

	/**
	 * Set a result for the spec.
	 *
	 * @param array $data The result data.
	 */
	public function log($type, $data = []) {
		$data['type'] = $type;
		$data += ['messages' => $this->messages()];
		$this->_results[$type][] = $data;
		$this->report($type, $data);
	}

	/**
	 * Return all parent instances.
	 *
	 * @param  boolean $current If `true` include `$this` to the list.
	 * @return array.
	 */
	public function _parents($current = false) {
		$instances = [];
		$instance = $current ? $this : $this->_parent;
		while ($instance !== null) {
			$instances[] = $instance;
			$instance = $instance->_parent;
		}
		return array_reverse($instances);
	}

	/**
	 * Bind the closure to the current context.
	 *
	 * @param  Closure  $closure The variable to check
	 * @param  string   $name Name of the parent type (TODO: to use somewhere).
	 * @throws Throw an Exception if the passed parameter is not a closure
	 */
	protected function _bind($closure, $name) {
		if (!is_callable($closure)) {
			throw new Exception("Error invalid closure.");
		}
		return $closure->bindTo($this);
	}

	/**
	 * Gets/Sets exclusive mode.
	 *
	 * @param  boolean|null For the setter behavior.
	 * @return boolean
	 */
	public function exclusive($value = null) {
		if ($value === null) {
			return $this->_exclusive;
		}
		return $this->_exclusive = $value;
	}

	/**
	 * Apply exclusivity up to the root.
	 *
	 * @param string The scope value
	 */
	protected function _emitExclusive($scope = 'exclusive') {
		if ($scope !== 'exclusive') {
			return;
		}
		$this->_root->_exclusives[] = Debugger::backtrace(['start' => 4]);
		$instances = $this->_parents(true);
		foreach ($instances as $instance) {
			$instance->exclusive(true);
		}
	}

	/**
	 * Get the active scope instance.
	 *
	 * @return object The object instance or `null` if there's no active instance.
	 */
	public static function current() {
		return end(static::$_instances);
	}

	/**
	 * Send a report
	 *
	 * @param string $type The name of the report.
	 * @param array  $data The data to report.
	 */
	public function report($type, $data = null) {
		if (!$this->_root->_reporters) {
			return;
		}
		$this->_root->_reporters->process($type, $data);
	}
}

?>