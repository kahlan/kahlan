<?php
namespace kahlan;

use Exception;
use InvalidArgumentException;
use set\Set;
use kahlan\PhpErrorException;
use kahlan\analysis\Debugger;

class Suite extends Scope
{
    /**
     * Store all hashed references.
     *
     * @var array
     */
    protected static $_registered = [];

    /**
     * The return status value (`0` for success).
     *
     * @var integer
     */
    protected $_status = null;

    /**
     * Matcher instance for the test suite
     *
     * @var array
     */
    protected $_matcher = null;

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
     * Set the number of fails allowed before aborting. `0` mean no fast fail.
     *
     * @see ::failfast()
     * @var integer
     */
    protected $_ff = 0;

    /**
     * Count the number of failure or exception.
     *
     * @see ::failfast()
     * @var integer
     */
    protected $_failure = 0;

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
    public function __construct($options = [])
    {
        $defaults = [
            'message' => '',
            'closure' => null,
            'parent' => null,
            'name' => 'describe',
            'scope' => 'normal',
            'matcher' => null
        ];
        $options += $defaults;
        extract($options);

        if (!$parent) {
            $this->_matcher = $matcher;
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
    public function describe($message, $closure, $scope = 'normal')
    {
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
    public function context($message, $closure, $scope = 'normal')
    {
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
     * @param  string         $scope   The scope.
     * @return $this
     */
    public function it($message, $closure = null, $scope = 'normal')
    {
        static $inc = 1;
        if ($closure === null) {
            $closure = $message;
            $message = "spec #" . $inc++;
        }
        $parent = $this;
        $root = $this->_root;
        $matcher = $this->_root->_matcher;
        $spec = new Spec(compact('message', 'closure', 'parent', 'root', 'scope', 'matcher'));
        $this->_childs[] = $spec;
        return $this;
    }

    /**
     * Comments out a group/class related spec.
     *
     * @param  string  $message Description message.
     * @param  Closure $closure A test case closure.
     * @return $this
     */
    public function xdescribe($message, $closure)
    {
    }

    /**
     * Comments out a context related spec.
     *
     * @param  string  $message Description message.
     * @param  Closure $closure A test case closure.
     * @return $this
     */
    public function xcontext($message, $closure)
    {
    }

    /**
     * Comments out a spec.
     *
     * @param  string|Closure $message Description message or a test closure.
     * @param  Closure|null   $closure A test case closure or `null`.
     * @return $this
     */
    public function xit($message, $closure = null)
    {
    }

    /**
     * Adds an exclusive group/class related spec.
     *
     * @param  string  $message Description message.
     * @param  Closure $closure A test case closure.
     * @return $this
     */
    public function ddescribe($message, $closure)
    {
        return $this->describe($message, $closure, 'exclusive');
    }

    /**
     * Adds an exclusive context related spec.
     *
     * @param  string  $message Description message.
     * @param  Closure $closure A test case closure.
     * @return $this
     */
    public function ccontext($message, $closure)
    {
        return $this->context($message, $closure, 'exclusive');
    }

    /**
     * Adds an exclusive spec.
     *
     * @param  string|Closure $message Description message or a test closure.
     * @param  Closure|null   $closure A test case closure or `null`.
     * @return $this
     */
    public function iit($message, $closure = null)
    {
        return $this->it($message, $closure, 'exclusive');
    }

    /**
     * Executed before tests.
     *
     * @param  Closure $closure A closure
     * @return $this
     */
    public function before($closure)
    {
        $this->_bind($closure, 'before');
        $this->_callbacks['before'][] = $closure;
        return $this;
    }

    /**
     * Executed after tests.
     *
     * @param Closure $closure A closure
     */
    public function after($closure)
    {
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
    public function beforeEach($closure)
    {
        $this->_bind($closure, 'beforeEach');
        $this->_callbacks['beforeEach'][] = $closure;
        return $this;
    }

    /**
     * Executed after each tests.
     *
     * @param Closure $closure A closure
     */
    public function afterEach($closure)
    {
        $this->_bind($closure, 'afterEach');
        $this->_callbacks['afterEach'][] = $closure;
        return $this;
    }

    /**
     * Process specs.
     *
     * @return array Process options.
     */
    protected function _run($options = [])
    {
        if ($this->_locked) {
            throw new Exception('Method not allowed in this context.');
        }

        $this->_locked = true;
        static::$_instances[] = $this;
        $this->_errorHandler(true, $options);

        try {
            $this->_callbacks('before', false);

            foreach($this->_childs as $child) {
                if ($this->failfast()) {
                    break;
                }
                $this->_process($child);
            }

            $this->_callbacks('after', false);
        } catch (Exception $exception) {
            try {
                $this->_callbacks('after', false);
            } catch (Exception $exception) {}
            $this->_exception($exception);
        }

        $this->_errorHandler(false);
        array_pop(static::$_instances);
        $this->_locked = false;
    }

    /**
     * Returns `true` if the suite reach the number of allowed failure by the fail-fast parameter.
     *
     * @return boolean;
     */
    public function failfast() {
        return $this->_root->_ff && $this->_root->_failure >= $this->_root->_ff;
    }

    /**
     * Process a child specs.
     *
     * @see kahlan\Suite::process()
     * @param object A child spec.
     */
    protected function _process($child)
    {
        if ($this->_root->exclusive() && !$child->exclusive()) {
            return;
        }
        if ($child instanceof Suite) {
            $child->_run();
            return;
        }
        try {
            $this->report('before');
            $this->_callbacks('beforeEach');
            $child->process();
            $this->_autoclear();
            $this->_callbacks('afterEach');
            $this->report('after');
        } catch (Exception $exception) {
            $this->_exception($exception);
            try {
                $this->_autoclear();
                $this->_callbacks('afterEach');
                $this->report('after');
            } catch (Exception $exception) {}
        }
        $this->report('progress');
    }

    /**
     * Return a each callback.
     *
     * @param string $name The name of the callback (i.e `'beforeEach'` or `'afterEach'`).
     */
    protected function _callbacks($name, $recursive = true)
    {
        $instances = $recursive ? $this->_parents(true) : [$this];
        foreach ($instances as $instance) {
            foreach($instance->_callbacks[$name] as $closure) {
                $closure($this);
            }
        }
    }

    /**
     * Override the default error handler
     *
     * @param boolean $enable If `true` override the default error handler,
     *                if `false` restore the default handler.
     * @param array   $options An options array. Available options are:
     *                - 'handler': An error handler closure.
     */
    protected function _errorHandler($enable, $options = [])
    {
        $defaults = ['handler' => null];
        $options += $defaults;
        if (!$enable) {
            return restore_error_handler();
        }
        $handler = function($code, $message, $file, $line = 0, $args = []) {
            $trace = debug_backtrace();
            $trace = array_slice($trace, 1, count($trace));
            $message = "`" . Debugger::errorType($code) . "` {$message}";
            $code = 0;
            $exception = compact('code', 'message', 'file', 'line', 'trace');
            throw new PhpErrorException($exception);
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
    public function run($options = [])
    {
        $defaults = ['reporters' => null, 'autoclear' => [], 'ff' => 0];
        $options += $defaults;

        $build = $this->_build();

        $this->_reporters = $options['reporters'];
        $this->_autoclear = (array) $options['autoclear'];
        $this->_ff = $options['ff'];

        $total = $this->exclusive() ? $build['exclusive'] : $build['specs'];
        $this->report('begin', ['total' => $total]);

        $this->_run();

        $report = [];
        $report['specs'] = $this->_results;
        $report['exclusives'] = $this->_exclusives;
        $this->report('end', $report);

        return $this->passed();
    }

    /**
     * Trigger the `stop` event.
     */
    public function stop()
    {
        $report = [];
        $report['specs'] = $this->_results;
        $report['exclusives'] = $this->_exclusives;
        $this->report('stop', $report);
    }

    /**
     * Build the suite.
     *
     * @return array Process options.
     */
    protected function _build()
    {
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
     * Returns an exit status code according passed results.
     *
     * @param  integer $status If set force a specific status to be retruned.
     * @return boolean         Returns `0` if no error occurred, `-1` otherwise.
     */
    public function status($status = null)
    {
        if ($status !== null) {
            $this->_status = $status;
        }

        if ($this->_status !== null) {
            return $this->_status;
        }

        if ($this->exclusive()) {
            return -1;
        }
        return $this->passed() ? 0 : -1;
    }

    /**
     * Checks if all test passed.
     *
     * @return boolean Returns `true` if no error occurred, `false` otherwise.
     */
    public function passed()
    {
        if (empty($this->_results['failed']) && empty($this->_results['exceptions']) && empty($this->_results['incomplete'])) {
            return true;
        }
        return false;
    }

    /**
     * Gets childs.
     *
     * @return array The array of childs instances.
     */
    public function childs()
    {
        return $this->_childs;
    }

    /**
     * Gets callbacks.
     *
     * @param  string $type The type of callbacks to get.
     * @return array        The array callbacks instances.
     */
    public function callbacks($type)
    {
        return isset($this->_callbacks[$type]) ? $this->_callbacks[$type] : [];
    }

    /**
     * Gets specs excecution results.
     *
     * @return array
     */
    public function results()
    {
        return $this->_results;
    }

    /**
     * Gets references of runned exclusives specs.
     *
     * @return array
     */
    public function exclusives()
    {
        return $this->_exclusives;
    }

    /**
     * Autoclear plugins.
     */
    protected function _autoclear()
    {
        foreach ($this->_root->_autoclear as $plugin) {
            if (method_exists($plugin, 'clear')) {
                is_object($plugin) ? $plugin->clear() : $plugin::clear();
            }
        }
        static::clear();
    }

    /**
     * Apply exclusivity downward to the lead.
     *
     * @param string The scope value
     */
    protected function _broadcastExclusive($scope = 'exclusive')
    {
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
     * Generate a hash from an instance or a string.
     *
     * @param  mixed $reference An instance or a fully namespaced class name.
     * @return string           A string hash.
     * @throws InvalidArgumentException
     */
    public static function hash($reference)
    {
        if (is_object($reference)) {
            return spl_object_hash($reference);
        }
        if (is_string($reference)) {
            return $reference;
        }
        throw new InvalidArgumentException("Error, the passed argument is not hashable.");
    }

    /**
     * Register a hash. [Mainly used for optimization]
     *
     * @param  mixed  $hash A hash to register.
     */
    public static function register($hash) {
        static::$_registered[$hash] = true;
    }

    /**
     * Get registered hashes. [Mainly used for optimizations]
     *
     * @param  string  $hash The hash to look up. If `null` return all registered hashes.
     */
    public static function registered($hash = null) {
        if(!$hash) {
            return static::$_registered;
        }
        return isset(static::$_registered[$hash]);
    }

    /**
     * Clear the registered hash.
     */
    public static function clear()
    {
        static::$_registered = [];
    }

}
