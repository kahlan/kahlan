<?php
namespace kahlan;

use Exception;
use kahlan\analysis\Debugger;
use kahlan\analysis\Inspector;

class Matcher
{
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
     * Deferred expectation.
     *
     * @var boolean
     */
    protected $_defered = [];

    /**
     * Registers a matcher.
     *
     * @param string $name The name of the matcher.
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
    public static function get($name)
    {
        return isset(static::$_matchers[$name]) ? static::$_matchers[$name] : null;
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
     * @param  object  The parent context class.
     * @return boolean
     */
    public function expect($actual, $parent)
    {
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
    public function __call($matcher, $params)
    {
        if (isset(static::$_matchers[$matcher])) {
            $class = static::$_matchers[$matcher];
            array_unshift($params, $this->_actual);
            $result = call_user_func_array($class . '::match', $params);
            $params = Inspector::parameters($class, 'match', $params);
            if (!is_object($result)) {
                $success = $result;
                $success = $this->_not ? !$success : $success;
                $description = $success ? '' : $class::description(compact('class', 'matcher', 'params'));
                $this->_result($result, compact('class', 'matcher', 'params', 'description'));
                return $this;
            }
            $this->_defered[] = compact('class', 'matcher', 'params') + [
                'instance' => $result, 'not' => $this->_not
            ];
            return $result;
        }
        throw new Exception("Error Undefined Matcher `{$matcher}`");
    }

    /**
     * Resolve defered matchers.
     */
    public function resolve()
    {
        foreach($this->_defered as $defered) {
            extract($defered);
            $this->_not = $not;
            $data = compact('class', 'matcher', 'params', 'instance');
            $boolean = $instance->resolve($data);
            if ($not ? $boolean : !$boolean) {
                $data['description'] = $class::description($data);
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
    protected function _result($boolean, $data = [])
    {
        $actual = $this->_actual;
        $not = $this->_not;
        $pass = $not ? !$boolean : $boolean;
        $type = $pass ? 'pass' : 'fail';
        if (!$pass) {
            $data += [
                'exception' => Debugger::backtrace([
                    'start' => defined('HHVM_VERSION') ? 2 : 3
                ])
            ];
        }
        $this->_parent->$type($data + compact('not', 'actual'));
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
     * Reset the class.
     */
    public static function reset()
    {
        static::$_matchers = [];
    }
}
