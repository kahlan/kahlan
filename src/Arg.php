<?php
namespace kahlan;

use Exception;

class Arg
{
    /**
     * Class dependencies.
     *
     * @var array
     */
    protected static $_classes = [
        'matcher' => 'kahlan\Matcher'
    ];

    /**
     * The matcher name.
     *
     * @var string
     */
    protected $_name = '';

    /**
     * The array of fully namespaced matcher classname.
     *
     * @var array
     */
    protected $_matchers = [];

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

    /**
     * Constructor
     *
     * @param array $config The argument matcher options. Possible values are:
     *                      - `'not'`     _boolean_: indicate if the matcher is a negative matcher.
     *                      - `'matcher'` _string_ : the fully namespaced matcher class name.
     *                      - `'params'`  _string_ : the expected parameters.
     */
    public function __construct($config = [])
    {
        $defaults = ['name' => '', 'not' => false, 'matchers' => [], 'params' => []];
        $config += $defaults;

        $this->_name     = $config['name'];
        $this->_not      = $config['not'];
        $this->_matchers = $config['matchers'];
        $this->_params   = $config['params'];
    }

    /**
     * Create an Argument Matcher
     *
     * @param  string  $name   The name of the matcher.
     * @param  array   $params The parameters to pass to the matcher.
     * @return boolean
     */
    public static function __callStatic($name, $params)
    {
        $not = false;
        if (preg_match('/^not/', $name)) {
            $matcher = lcfirst(substr($name, 3));
            $not = true;
        } else {
            $matcher = $name;
        }
        $class = static::$_classes['matcher'];
        if ($matchers = $class::get($matcher, true)) {
            return new static(compact('name', 'matchers', 'not', 'params'));
        }
        throw new Exception("Unexisting matchers attached to `'{$name}'`.");
    }

    /**
     * Check if `$actual` matches the matcher.
     *
     * @param  string  $name The actual value.
     * @return boolean       Returns `true` on success and `false` otherwise.
     */
    public function match($actual)
    {
        $matcher = null;
        foreach ($this->_matchers as $target => $value) {
            if (!$target) {
                $matcher = $value;
                continue;
            }
            if ($actual instanceof $target) {
                $matcher = $value;
            }
        }
        if (!$matcher) {
            throw new Exception("Unexisting matcher attached to `'{$this->_name}'` for `{$target}`.");
        }
        $params = $this->_params;
        array_unshift($params, $actual);
        $boolean = call_user_func_array($matcher . '::match', $params);
        return $this->_not ? !$boolean : $boolean;
    }
}
