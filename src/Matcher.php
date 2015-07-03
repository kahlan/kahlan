<?php
namespace kahlan;

use Exception;
use kahlan\analysis\Inspector;

/**
 * Class Matcher
 *
 * @method Matcher toBe(mixed $expected) passes if actual === expected
 * @method Matcher toEqual(mixed $expected) passes if actual == expected
 * @method Matcher toBeTruthy() passes if actual is truthy
 * @method Matcher toBeFalsy() passes if actual is falsy
 * @method Matcher toBeEmpty() passes if actual is falsy
 * @method Matcher toBeNull() passes if actual is null
 * @method Matcher toBeA(string $expected) passes if actual is of the expected type
 * @method Matcher toBeAn(string $expected) passes if actual is of the expected type (toBeA alias)
 * @method Matcher toBeAnInstanceOf(string $expected) passes if actual is an instance of expected
 * @method Matcher toHaveLength(int $expected) passes if actual has the expected length
 * @method Matcher toContain(mixed $expected) passes if actual contain the expected value
 * @method Matcher toContainKey(mixed $expected) passes if actual contain the expected key
 * @method Matcher toContainKeys(mixed $expected) passes if actual contain the expected keys (toContainKey alias)
 * @method Matcher toBeCloseTo(float $expected, int $precision) passes if actual is close to expected in some precision
 * @method Matcher toBeGreaterThan(mixed $expected) passes if actual if greater than expected
 * @method Matcher toBeLessThan(mixed $expected) passes if actual is less than expected
 * @method Matcher toThrow(mixed $expected = null) passes if actual throws the expected exception
 * @method Matcher toMatch(string $expected) passes if actual matches the expected regexp
 * @method Matcher toEcho(string $expected) passes if actual echoes the expected string
 * @method Matcher toMatchEcho(string $expected) passes if actual echoes matches the expected string
 * @method Matcher toReceive(string $expected) passes if the expected method as been called on actual
 * @method Matcher toReceiveNext(string $expected) passes if the expected method as been called on actual after some other method
 */
class Matcher
{
    /**
     * The matchers list
     *
     * @var array
     */
    protected static $_matchers = [];

    /**
     * The spec context instance.
     *
     * @var object
     */
    protected $_spec = null;

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
     * Deferred expectation.
     *
     * @var boolean
     */
    protected $_deferred = [];

    /**
     * Registers a matcher.
     *
     * @param string $name  The name of the matcher.
     * @param string $class A fully-namespaced class name.
     */
    public static function register($name, $class)
    {
        static::$_matchers[$name] = $class;
    }

    /**
     * Returns registered matchers.
     *
     * @param  string $name The name of the matcher.
     * @return array        The registered matchers or a fully-namespaced class name if $name is not null.
     */
    public static function get($name = null)
    {
        if ($name) {
            return isset(static::$_matchers[$name]) ? static::$_matchers[$name] : null;
        }
        return static::$_matchers;
    }

    /**
     * Checks if a matcher is registered.
     *
     * @param  string  $name The name of the matcher.
     * @return boolean returns `true` if the matcher exists, `false` otherwise.
     */
    public static function exists($name)
    {
        return isset(static::$_matchers[$name]);
    }

    /**
     * Unregisters a matcher.
     *
     * @param mixed $name The name of the matcher. If name is `true` unregister all
     *        the matchers.
     */
    public static function unregister($name)
    {
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
     * @param  object  The spec context.
     * @return Matcher
     */
    public function expect($actual, $spec)
    {
        $this->_not = false;
        $this->_spec = $spec;
        $this->_actual = $actual;
        return $this;
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
        if (!isset(static::$_matchers[$matcherName])) {
            throw new Exception("Error, undefined matcher `{$matcherName}`.");
        }
        $matcher = static::$_matchers[$matcherName];
        array_unshift($params, $this->_actual);
        $result = call_user_func_array($matcher . '::match', $params);
        $params = Inspector::parameters($matcher, 'match', $params);
        if (!is_object($result)) {
            $data = compact('matcherName', 'matcher', 'params');
            $data['description'] = $matcher::description();
            $this->_result($result, $data);
            return $this;
        }
        $this->_deferred[] = compact('matcherName', 'matcher', 'params') + [
            'instance' => $result, 'not' => $this->_not
        ];
        return $result;
    }

    /**
     * Resolves deferred matchers.
     */
    public function resolve()
    {
        foreach($this->_deferred as $data) {
            $instance = $data['instance'];
            $this->_not = $data['not'];
            $boolean = $instance->resolve();
            $data['description'] = $instance->description();
            $data['backtrace'] = $instance->backtrace();
            $this->_result($boolean, $data);
        }
        $this->_deferred = [];
        $this->_not = false;
    }

    /**
     * Sends the result to the callee & clear states.
     *
     * @param  boolean $boolean Set `true` for success and `false` for failure.
     * @param  array   $data    Test details array.
     * @return boolean
     */
    protected function _result($boolean, $data = [])
    {
        $not = $this->_not;
        $pass = $not ? !$boolean : $boolean;
        $type = $pass ? 'pass' : 'fail';

        $description = $data['description'];
        if (is_array($description)) {
            $data['params'] = $description['params'];
            $data['description'] = $description['description'];
        }
        $this->_spec->report()->add($type, $data + compact('not'));
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
        if ($name === 'not') {
            $this->_not = !$this->_not;
            return $this;
        }
    }

    /**
     * Resets the class.
     */
    public static function reset()
    {
        static::$_matchers = [];
    }
}
