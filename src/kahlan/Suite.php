<?php
/**
 * Kahlan: PHP Testing Framework
 *
 * @copyright     Copyright 2013, CrysaLEAD
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace kahlan;

use Exception;
use kahlan\util\Set;
use kahlan\analysis\Debugger;

class Suite extends Scope {

	/**
	 * DI container
	 *
	 * @var array
	 */
	protected $_box = null;

	/**
	 * The childs array.
	 *
	 * @var array
	 */
	protected $_childs = [];

	/**
	 * The each callbacks.
	 *
	 * @var array
	 */
	protected $_callbacks = [
		'before' => [],
		'after' => [],
		'beforeEach' => [],
		'afterEach' => []
	];

	/**
	 * Boolean lock which avoid `process()` to be called in tests
	 *
	 * @see khakan\Suite::process()
	 */
	protected $_locked = false;

	/**
	 * The reporters container.
	 *
	 * @var object
	 */
	protected $_reporters = null;

	/**
	 * Array of fully-namespaced class name to clear on each `it()`.
	 *
	 * @var array
	 */
	protected $_autoclear = [];

	/**
	 * Saved backtrace for the exclusive mode.
	 *
	 * @see kahlan\Scope::_emitExclusive()
	 * @var array
	 */
	protected $_exclusives = [];

	/**
	 * Constructor.
	 *
	 * @param array $options The Suite config array. Options are:
	 *              -`'message'` : the description message.
	 *              -`'closure'` : the closure of the test.
	 *              -`'parent'` : the parent suite instance.
	 *              -`'name'` : the type of the suite.
	 *              -`'scope'` : supported scope are `'normal'` & `'exclusive'`.
	 */
	public function __construct($options = []) {
		$defaults = [
			'message' => '',
			'closure' => null,
			'parent' => null,
			'name' => 'describe',
			'scope' => 'normal',
			'box' => null
		];
		$options += $defaults;
		extract($options);

		if (!$parent) {
			$this->_box = $box;
			$this->_root = $this;
			return;
		}
		$this->_root = $parent->_root;
		$closure = $this->_bind($closure, $name);
		$this->_message = $message;
		$this->_closure = $closure;
		$this->_parent = $parent;
		$this->_emitExclusive($scope);
	}

	/**
	 * Add a group/class related spec.
	 *
	 * @param  string  $message Description message.
	 * @param  Closure $closure A test case closure.
	 * @return $this
	 */
	public function describe($message, $closure, $scope = 'normal') {
		$parent = $this;
		$name = 'describe';
		$suite = new Suite(compact('message', 'closure', 'parent', 'name', 'scope'));
		return $this->_childs[] = $suite;
	}

	/**
	 * Add a context related spec.
	 *
	 * @param  string  $message Description message.
	 * @param  Closure $closure A test case closure.
	 * @return $this
	 */
	public function context($message, $closure, $scope = 'normal') {
		$parent = $this;
		$name = 'context';
		$suite = new Suite(compact('message', 'closure', 'parent', 'name', 'scope'));
		return $this->_childs[] = $suite;
	}

	/**
	 * Add a spec.
	 *
	 * @param  string|Closure $message Description message or a test closure.
	 * @param  Closure|null   $closure A test case closure or `null`.
	 * @param  string         $scope The scope.
	 * @return $this
	 */
	public function it($message, $closure = null, $scope = 'normal') {
		static $inc = 1;
		if ($closure === null) {
			$closure = $message;
			$message = "Test #" . $inc++;
		}
		$parent = $this;
		$root = $this->_root;
		$box = $this->_root->_box;
		$spec = new Spec(compact('message', 'closure', 'parent', 'root', 'scope', 'box'));
		$this->_childs[] = $spec;
		return $this;
	}

	/**
	 * Add a group/class related spec.
	 *
	 * @param  string  $message Description message.
	 * @param  Closure $closure A test case closure.
	 * @return $this
	 */
	public function xdescribe($message, $closure) {
		return $this->describe($message, $closure, 'exclusive');
	}

	/**
	 * Add a context related spec.
	 *
	 * @param  string  $message Description message.
	 * @param  Closure $closure A test case closure.
	 * @return $this
	 */
	public function xcontext($message, $closure) {
		return $this->context($message, $closure, 'exclusive');
	}

	/**
	 * Add a spec.
	 *
	 * @param  string|Closure $message Description message or a test closure.
	 * @param  Closure|null   $closure A test case closure or `null`.
	 * @return $this
	 */
	public function xit($message, $closure = null) {
		return $this->it($message, $closure, 'exclusive');
	}

	/**
	 * Executed before tests.
	 *
	 * @param  Closure $closure A closure
	 * @return $this
	 */
	public function before($closure) {
		$this->_bind($closure, 'before');
		$this->_callbacks['before'][] = $closure;
		return $this;
	}

	/**
	 * Executed after tests.
	 *
	 * @param Closure $closure A closure
	 */
	public function after($closure) {
		$this->_bind($closure, 'after');
		$this->_callbacks['after'][] = $closure;
		return $this;
	}

	/**
	 * Executed before each tests.
	 *
	 * @param  Closure $closure A closure
	 * @return $this
	 */
	public function beforeEach($closure) {
		$this->_bind($closure, 'beforeEach');
		$this->_callbacks['beforeEach'][] = $closure;
		return $this;
	}

	/**
	 * Executed after each tests.
	 *
	 * @param Closure $closure A closure
	 */
	public function afterEach($closure) {
		$this->_bind($closure, 'afterEach');
		$this->_callbacks['afterEach'][] = $closure;
		return $this;
	}

	/**
	 * Process specs.
	 *
	 * @return array Process options.
	 */
	protected function _run($options = []) {
		if ($this->_locked) {
			throw new Exception('Method not allowed in this context.');
		}

		$this->_locked = true;
		static::$_instances[] = $this;
		$this->_errorHandler(true, $options);

		try {
			$this->_callbacks('before', false);

			foreach($this->_childs as $child) {
				$this->_process($child);
			}

			$this->_callbacks('after', false);
		} catch (Exception $exception) {
			$this->_exception($exception);
			try {
				$this->_callbacks('after', false);
			} catch (Exception $exception) {}
		}

		$this->_errorHandler(false);
		array_pop(static::$_instances);
		$this->_locked = false;
	}

	/**
	 * Process a child specs.
	 *
	 * @see kahlan\Suite::process()
	 * @param object A child spec.
	 */
	protected function _process($child) {
		if ($this->_root->exclusive() && !$child->exclusive()) {
			return;
		}
		if ($child instanceof Suite) {
			$child->_run();
			return;
		}
		try {
			$this->_callbacks('beforeEach');
			$child->process();
			$this->_autoclear();
			$this->_callbacks('afterEach');
		} catch (Exception $exception) {
			$this->_exception($exception);
			try {
				$this->_autoclear();
				$this->_callbacks('afterEach');
			} catch (Exception $exception) {}
		}
		$this->report('progress');
	}

	/**
	 * Return a each callback.
	 *
	 * @param string $name The name of the callback (i.e `'beforeEach'` or `'afterEach'`).
	 */
	protected function _callbacks($name, $recursive = true) {
		$instances = $recursive ? $this->_parents(true) : [$this];
		foreach ($instances as $instance) {
			foreach($instance->_callbacks[$name] as $closure) {
				$closure($this);
			}
		}
	}

	/**
	 * Getter which return the runned tests result array.
	 *
	 * @return array
	 */
	public function results() {
		$results = $this->_results;
		foreach ($this->_childs as $child) {
			foreach ($child->results() as $type => $result) {
				$results[$type] = array_merge($results[$type], $result);
			}
		}
		return $results;
	}

	/**
	 * Override the default error handler
	 *
	 * @param boolean $enable If `true` override the default error handler,
	 *                if `false` restore the default handler.
	 * @param array   $options An options array. Available options are:
	 *                - 'handler': An error handler closure.
	 */
	protected function _errorHandler($enable, $options = []) {
		$defaults = ['handler' => null];
		$options += $defaults;
		if (!$enable) {
			return restore_error_handler();
		}
		$handler = function($code, $message, $file, $line = 0, $args = []) {
			$trace = debug_backtrace(false);
			$trace = array_slice($trace, 1, count($trace));
			$instance = Spec::current() ?: static::current();
			$messages = $instance->messages();
			$exception = compact('code', 'message', 'file', 'line', 'trace', 'args');
			$this->exception(compact('messages', 'exception'));
		};
		$options['handler'] = $options['handler'] ?: $handler;
		set_error_handler($options['handler']);
	}

	/**
	 * Run all specs.
	 *
	 * @param  array $options Run options.
	 * @return array The result array.
	 */
	public function run($options = []) {
		$defaults = ['reporters' => null, 'autoclear' => []];
		$options += $defaults;

		$build = $this->_build();

		$this->_reporters = $options['reporters'];
		$this->_autoclear = (array) $options['autoclear'];

		$total = $this->exclusive() ? $build['exclusive'] : $build['specs'];
		$this->report('begin', ['total' => $total]);

		$this->_run();
		foreach ($this->results() as $type => $result) {
			$this->_results[$type] = array_merge($this->_results[$type], $result);
		}
		$report['specs'] = $this->_results;
		$report['exclusive'] = $this->_exclusives;

		$this->report('end', $report);
		return $this->_results;
	}

	/**
	 * Build the suite.
	 *
	 * @return array Process options.
	 */
	protected function _build() {
		static::$_instances[] = $this;
		$closure = $this->_closure;
		if (is_callable($closure)) {
			$closure($this);
		}
		$specs = 0;
		$exclusive = 0;
		foreach($this->_childs as $child) {
			if ($child instanceof Suite) {
				$result = $child->_build();
				if ($result['exclusive']) {
					$exclusive += $result['exclusive'];
				} elseif ($child->exclusive()) {
					$exclusive += $result['specs'];
					$child->_broadcastExclusive();
				} else {
					$specs += $result['specs'];
				}
			} else {
				$child->exclusive() ? $exclusive++ : $specs++;
			}
		}
		array_pop(static::$_instances);
		return compact('specs', 'exclusive');
	}

	/**
	 * Stop the script and return an exit status code according passed results.
	 */
	public function stop() {
		$results = $this->_results;

		if ($this->exclusive()) {
			exit(-1);
		}
		if (!isset($results['fail']) || !isset($results['exception']) || !isset($results['incomplete'])) {
			exit(-1);
		}
		if (empty($results['fail']) && empty($results['exception']) && empty($results['incomplete'])) {
			exit(0);
		}
		exit(-1);
	}

	/**
	 * Autoclear plugins.
	 */
	protected function _autoclear() {
		foreach ($this->_root->_autoclear as $plugin) {
			if (method_exists($plugin, 'clear')) {
				is_object($plugin) ? $plugin->clear() : $plugin::clear();
			}
		}
	}

	/**
	 * Apply exclusivity downward to the lead.
	 *
	 * @param string The scope value
	 */
	protected function _broadcastExclusive($scope = 'exclusive') {
		if ($scope !== 'exclusive') {
			return;
		}
		$instances = $this->_parents(true);
		foreach ($this->_childs as $child) {
			$child->exclusive(true);
			if ($child instanceof Suite) {
				$child->_broadcastExclusive($scope);
			}
		}
	}

	/**
	 * Reset the class
	 */
	public function reset() {
		$this->_childs = [];
		$this->_autoclear = [];
		$this->_reporters = null;
		$this->_exclusives = [];
	}
}

?>