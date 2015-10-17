<?php
namespace kahlan;

use Exception;
use code\Code;
use code\TimeoutException;
use kahlan\analysis\Inspector;
use kahlan\analysis\Debugger;

/**
 * Class Expectation
 *
 * @method Expectation toBe(mixed $expected) passes if actual === expected
 * @method Expectation toEqual(mixed $expected) passes if actual == expected
 * @method Expectation toBeTruthy() passes if actual is truthy
 * @method Expectation toBeFalsy() passes if actual is falsy
 * @method Expectation toBeEmpty() passes if actual is falsy
 * @method Expectation toBeNull() passes if actual is null
 * @method Expectation toBeA(string $expected) passes if actual is of the expected type
 * @method Expectation toBeAn(string $expected) passes if actual is of the expected type (toBeA alias)
 * @method Expectation toBeAnInstanceOf(string $expected) passes if actual is an instance of expected
 * @method Expectation toHaveLength(int $expected) passes if actual has the expected length
 * @method Expectation toContain(mixed $expected) passes if actual contain the expected value
 * @method Expectation toContainKey(mixed $expected) passes if actual contain the expected key
 * @method Expectation toContainKeys(mixed $expected) passes if actual contain the expected keys (toContainKey alias)
 * @method Expectation toBeCloseTo(float $expected, int $precision) passes if actual is close to expected in some precision
 * @method Expectation toBeGreaterThan(mixed $expected) passes if actual if greater than expected
 * @method Expectation toBeLessThan(mixed $expected) passes if actual is less than expected
 * @method Expectation toThrow(mixed $expected = null) passes if actual throws the expected exception
 * @method Expectation toMatch(string $expected) passes if actual matches the expected regexp
 * @method Expectation toEcho(string $expected) passes if actual echoes the expected string
 * @method Expectation toMatchEcho(string $expected) passes if actual echoes matches the expected string
 * @method Expectation toReceive(string $expected) passes if the expected method as been called on actual
 * @method Expectation toReceiveNext(string $expected) passes if the expected method as been called on actual after some other method
 */
class Expectation
{
    /**
     * Class dependencies.
     *
     * @var array
     */
    protected $_classes = [
        'specification' => 'kahlan\Specification',
        'matcher'       => 'matcher'
    ];

    /**
     * Deferred expectation.
     *
     * @var boolean
     */
    protected $_deferred = [];

    /**
     * Stores the success value.
     *
     * @var boolean
     */
    protected $_passed = true;

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
    protected $_timeout = -1;

    /**
     * Factory method.
     */
    public static function expect($actual, $timeout = -1)
    {
        return new static(compact('actual', 'timeout'));
    }

    /**
     * Constructor.
     *
     * @param array $config The config array. Options are:
     *                       -`'actual'`  _mixed_   : the actual value.
     *                       -`'timeout'` _integer_ : the timeout value.
     */
    public function __construct($config = [])
    {
        $defaults = [
            'actual'  => null,
            'timeout' => -1
        ];
        $config += $defaults;

        $this->_actual = $config['actual'];
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
     * @return boolean
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
     * @param  string  $name   The name of the matcher.
     * @param  array   $params The parameters to pass to the matcher.
     * @return boolean
     */
    public function __call($matcherName, $params)
    {
        $result = true;
        $spec = $this->_actual;
        $specification = $this->_classes['specification'];

        $closure = function() use ($spec, $specification, $matcherName, $params, &$actual, &$result) {
            if ($spec instanceof $specification) {
                $actual = $spec->run();
                if (!$spec->passed()) {
                    return false;
                }
            } else {
                $actual = $spec;
            }
            array_unshift($params, $actual);
            $matcher = $this->_matcher($matcherName, $actual);
            $result = call_user_func_array($matcher . '::match', $params);
            return is_object($result) || $result === !$this->_not;
        };

        try {
            $this->_spin($closure);
        } catch (TimeoutException $e) {
            $data['params']['timeout'] = $e->getMessage();
        } finally {
            array_unshift($params, $actual);
            $matcher = $this->_matcher($matcherName, $actual);
            $params = Inspector::parameters($matcher, 'match', $params);
            $data = compact('matcherName', 'matcher', 'params');
            if ($spec instanceof $specification) {
                foreach ($spec->logs() as $value) {
                    $this->_logs[] = $value;
                }
                $this->_passed = $this->_passed && $spec->passed();
            }
        }

        if (!is_object($result)) {
            $data['description'] = $data['matcher']::description();
            $this->_log($result, $data);
            return $this;
        }
        $this->_deferred[] = $data + [
            'instance' => $result, 'not' => $this->_not
        ];
        return $result;
    }

    /**
     * Returns a compatible matcher class name according to a passed actual value.
     *
     * @param  string $name   The name of the matcher.
     * @param  string $actual The actual value.
     * @return string         A matcher class name.
     */
    public function _matcher($matcherName, $actual)
    {
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
     * @param  string  $matcher The matcher class name.
     * @param  array   $args  The parameters to pass to the matcher.
     * @return mixed
     */
    public function run()
    {
        $spec = $this->_actual;
        $specification = $this->_classes['specification'];
        if (!$spec instanceof $specification) {
            $this->_passed = false;
            return $this;
        }

        $closure = function() use ($spec) {
            try {
                $spec->run();
            } catch (Exception $e) {}
            return $spec->passed();
        };

        try {
            $this->_spin($closure);
        } catch (TimeoutException $e) {
        } finally {
            foreach ($spec->logs() as $value) {
                $this->_logs[] = $value;
            }
            $this->_passed = $this->_passed && $spec->passed();
        }
        return $this;
    }

    /**
     * Runs the expectation.
     *
     * @param Closure $closure The closure to run/spin.
     */
    protected function _spin($closure)
    {
        if (($timeout = $this->timeout()) < 0) {
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
        foreach($this->_deferred as $data) {
            $instance = $data['instance'];
            $this->_not = $data['not'];
            $boolean = $instance->resolve();
            $data['description'] = $instance->description();
            $data['backtrace'] = $instance->backtrace();
            $this->_log($boolean, $data);
        }
        $this->_deferred = [];
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
            $data['type'] = 'pass';
        } else {
            $data['type'] ='fail';
            $this->_passed = false;
        }

        $description = $data['description'];
        if (is_array($description)) {
            $data['params'] = $description['params'];
            $data['description'] = $description['description'];
        }
        $data['backtrace'] = Debugger::backtrace();
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
     * Checks if all test passed.
     *
     * @return boolean Returns `true` if no error occurred, `false` otherwise.
     */
    public function passed()
    {
        if ($this->_deferred) {
            $this->_resolve();
        }
        return $this->_passed;
    }

    /**
     * Checks if all test passed.
     *
     * @return boolean Returns `true` if no error occurred, `false` otherwise.
     */
    public function runned()
    {
        if ($this->_deferred) {
            $this->_resolve();
        }
        return !empty($this->_logs);
    }

    /**
     * Clears the instance.
     */
    public function clear()
    {
        $this->_actual = null;
        $this->_passed = true;
        $this->_not = false;
        $this->_timeout = -1;
        $this->_logs = [];
        $this->_deferred = [];
    }
}
