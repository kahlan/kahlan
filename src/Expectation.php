<?php
namespace Kahlan;

use Throwable;
use Exception;
use Kahlan\Analysis\Debugger;
use Kahlan\Analysis\Inspector;
use Kahlan\Code\Code;
use Kahlan\Code\TimeoutException;
use Kahlan\Block\Specification;

use Closure;

/**
 * Class Expectation
 *
 * @method Matcher\ToBe toBe(mixed $expected) passes if actual === expected
 * @method Matcher\ToEqual toEqual(mixed $expected) passes if actual == expected
 * @method Matcher\ToBeTruthy toBeTruthy() passes if actual is truthy
 * @method Matcher\ToBeFalsy toBeFalsy() passes if actual is falsy
 * @method Matcher\ToBeFalsy toBeEmpty() passes if actual is falsy
 * @method Matcher\ToBeNull toBeNull() passes if actual is null
 * @method Matcher\ToBeA toBeA(string $expected) passes if actual is of the expected type
 * @method Matcher\ToBeA toBeAn(string $expected) passes if actual is of the expected type (toBeA alias)
 * @method Matcher\ToBeAnInstanceOf toBeAnInstanceOf(string $expected) passes if actual is an instance of expected
 * @method Matcher\ToHaveLength toHaveLength(int $expected) passes if actual has the expected length
 * @method Matcher\ToContain toContain(mixed $expected) passes if actual contain the expected value
 * @method Matcher\ToContainKey toContainKey(mixed $expected) passes if actual contain the expected key
 * @method Matcher\ToContainKey toContainKeys(mixed $expected) passes if actual contain the expected keys (toContainKey alias)
 * @method Matcher\ToBeCloseTo toBeCloseTo(float $expected, int $precision) passes if actual is close to expected in some precision
 * @method Matcher\ToBeGreaterThan toBeGreaterThan(mixed $expected) passes if actual if greater than expected
 * @method Matcher\ToBeLessThan toBeLessThan(mixed $expected) passes if actual is less than expected
 * @method Matcher\ToThrow toThrow(mixed $expected = null) passes if actual throws the expected exception
 * @method Matcher\ToMatch toMatch(string $expected) passes if actual matches the expected regexp
 * @method Matcher\ToEcho toEcho(string $expected) passes if actual echoes the expected string
 * @method Matcher\ToMatchEcho toMatchEcho(string $expected) passes if actual echoes matches the expected string
 * @method Matcher\ToReceive toReceive(string $expected) passes if the expected method as been called on actual
 * @method Exception toReceiveNext(string $expected) passes if the expected method as been called on actual after some other method
 *
 * @property Expectation $not
 */
class Expectation
{
    /**
     * Indicates whether the block has been processed or not.
     *
     * @var boolean
     */
    protected $_processed = false;

    /**
     * Deferred expectation.
     *
     * @var array
     */
    protected $_deferred = null;

    /**
     * Stores the success value.
     *
     * @var boolean
     */
    protected $_passed = null;

    /**
     * The result logs.
     *
     * @var array
     */
    protected $_logs = [];

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
     * The timeout value.
     *
     * @var integer
     */
    protected $_timeout = 0;

    /**
     * The delegated handler.
     *
     * @var callable
     */
    protected $_handler = null;

    /**
     * The supported exception type.
     *
     * @var string
     */
    protected $_type;

    /**
     * Constructor.
     *
     * @param array $config The config array. Options are:
     *                       -`'actual'`  _mixed_   : the actual value.
     *                       -`'timeout'` _integer_ : the timeout value.
     *                       Or:
     *                       -`'handler'` _Closure_ : a delegated handler to execute.
     *                       -`'type'`    _string_  : delegated handler supported exception type.
     *
     * @return Expectation
     */
    public function __construct($config = [])
    {
        $defaults = [
            'actual'  => null,
            'handler' => null,
            'type'    => 'Exception',
            'timeout' => 0
        ];
        $config += $defaults;

        $this->_actual = $config['actual'];
        $this->_handler = $config['handler'];
        $this->_type = $config['type'];
        $this->_timeout = $config['timeout'];
    }

    /**
     * Returns the actual value.
     *
     * @return boolean
     */
    public function actual()
    {
        return $this->_actual;
    }

    /**
     * Returns the not value.
     *
     * @return boolean
     */
    public function not()
    {
        return $this->_not;
    }

    /**
     * Returns the logs.
     */
    public function logs()
    {
        return $this->_logs;
    }

    /**
     * Returns the deferred expectations.
     *
     * @return array
     */
    public function deferred()
    {
        return $this->_deferred;
    }

    /**
     * Returns the timeout value.
     */
    public function timeout()
    {
        return $this->_timeout;
    }

    /**
     * Calls a registered matcher.
     *
     * @param  string  $matcherName The name of the matcher.
     * @param  array   $args        The arguments to pass to the matcher.
     * @return boolean
     */
    public function __call($matcherName, $args)
    {
        $result = true;
        $spec = $this->_actual;
        $this->_passed = true;

        $closure = function () use ($spec, $matcherName, $args, &$actual, &$result) {
            if ($spec instanceof Specification) {
                $spec->reset();
                $spec->process($result);
                $expectation = $spec->expect($result, 0)->__call($matcherName, $args);
                $this->_logs = $spec->logs();
                $this->_passed = $spec->passed() && $expectation->passed();
                return $this->_passed;
            } else {
                $actual = $spec;
                array_unshift($args, $actual);
                $matcher = $this->_matcher($matcherName, $actual);
                $result = call_user_func_array($matcher . '::match', $args);
            }
            return is_object($result) || $result === !$this->_not;
        };

        try {
            $this->_spin($closure);
        } catch (TimeoutException $e) {
        }

        if ($spec instanceof Specification) {
            if ($exception = $spec->log()->exception()) {
                throw $exception;
            }
            return $this;
        }

        array_unshift($args, $actual);
        $matcher = $this->_matcher($matcherName, $actual);
        $data = Inspector::parameters($matcher, 'match', $args);
        $report = compact('matcherName', 'matcher', 'data');

        if (!is_object($result)) {
            $report['description'] = $report['matcher']::description();
            $this->_log($result, $report);
            return $this;
        }
        $this->_deferred = $report + [
            'instance' => $result, 'not' => $this->_not,
        ];

        return $result;
    }

    /**
     * Returns a compatible matcher class name according to a passed actual value.
     *
     * @param  string $matcherName The name of the matcher.
     * @param  mixed  $actual      The actual value.
     * @return string              A matcher class name.
     */
    public function _matcher($matcherName, $actual)
    {
        $target = null;
        if (!Matcher::exists($matcherName, true)) {
            throw new Exception("Unexisting matcher attached to `'{$matcherName}'`.");
        }

        $matcher = null;

        foreach (Matcher::get($matcherName, true) as $target => $value) {
            if (!$target) {
                $matcher = $value;
                continue;
            }
            if ($actual instanceof $target) {
                $matcher = $value;
            }
        }

        if (!$matcher) {
            throw new Exception("Unexisting matcher attached to `'{$matcherName}'` for `{$target}`.");
        }

        return $matcher;
    }

    /**
     * Processes the expectation.
     *
     * @return mixed
     */
    protected function _process()
    {
        if (is_callable($this->_handler)) {
            return $this->_processDelegated();
        }
        $spec = $this->_actual;

        if (!$spec instanceof Specification || $spec->passed() !== null) {
            return $this;
        }

        $closure = function () use ($spec) {
            $spec->reset();
            $spec->process($result);
            $this->_logs = $spec->logs();
            return $this->_passed = $spec->passed();
        };

        try {
            $this->_spin($closure);
        } catch (TimeoutException $e) {
        }
        if ($exception = $spec->log()->exception()) {
            throw $exception;
        }

        $this->_passed = $spec->passed() && ($this->_passed ?? true);
        return $this;
    }

    /**
     * Processes the expectation.
     *
     * @return mixed
     */
    protected function _processDelegated()
    {
        $exception = null;

        try {
            call_user_func($this->_handler);
        } catch (Throwable|Exception $e) {
            $exception = $e;
        }

        if (!$exception) {
            $this->_logs[] = ['type' => 'passed'];
            $this->_passed = true;
            return $this;
        }

        $this->_passed = false;

        if (!$exception instanceof $this->_type) {
            throw $exception;
        }

        $this->_logs[] = [
            'type' => 'failed',
            'data' => [
                'external' => true,
                'description' => $exception->getMessage()
            ],
            'backtrace' => Debugger::normalize($exception->getTrace())
        ];
        return $this;
    }

    /**
     * Runs the expectation.
     *
     * @param Closure $closure The closure to run/spin.
     */
    protected function _spin($closure)
    {
        $timeout = $this->timeout();
        if ($timeout <= 0) {
            $closure();
        } else {
            Code::spin($closure, $timeout);
        }
    }

    /**
     * Resolves deferred matchers.
     */
    protected function _resolve()
    {
        if (!$this->_deferred) {
            return;
        }
        $data = $this->_deferred;

        $instance = $data['instance'];
        $this->_not = $data['not'];
        $boolean = $instance->resolve();
        $data['description'] = $instance->description();
        $data['backtrace'] = $instance->backtrace();
        $this->_log($boolean, $data);

        $this->_deferred = null;
    }

    /**
     * Logs a result.
     *
     * @param  boolean $boolean Set `true` for success and `false` for failure.
     * @param  array   $data    Test details array.
     * @return boolean
     */
    protected function _log($boolean, $data = [])
    {
        $not = $this->_not;
        $pass = $not ? !$boolean : $boolean;
        if ($pass) {
            $data['type'] = 'passed';
        } else {
            $data['type'] = 'failed';
            $this->_passed = false;
        }

        $description = $data['description'];
        if (is_array($description)) {
            $data['data'] = $description['data'];
            $data['description'] = $description['description'];
        }
        $data += ['backtrace' => Debugger::backtrace()];
        $data['not'] = $not;

        $this->_logs[] = $data;
        $this->_not = false;
        return $boolean;
    }

    /**
     * Magic getter, if called with `'not'` invert the `_not` attribute.
     *
     * @param string
     */
    public function __get($name)
    {
        if ($name !== 'not') {
            throw new Exception("Unsupported attribute `{$name}`.");
        }
        $this->_not = !$this->_not;
        return $this;
    }

    /**
     * Run the expectation.
     *
     * @return boolean Returns `true` if passed, `false` otherwise.
     */
    public function process()
    {
        if (!$this->_processed) {
            $this->_process();
            $this->_resolve();
        }
        $this->_processed = true;
        return $this->_passed !== false;
    }

    /**
     * Returns `true`/`false` if test passed or not, `false` if not and `null` if not runned.
     *
     * @return boolean.
     */
    public function passed()
    {
        return $this->_passed;
    }

    /**
     * Clears the instance.
     */
    public function clear()
    {
        $this->_actual = null;
        $this->_passed = null;
        $this->_processed = null;
        $this->_not = false;
        $this->_timeout = 0;
        $this->_logs = [];
        $this->_deferred = null;
    }
}
