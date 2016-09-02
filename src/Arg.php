<?php
namespace Kahlan;

use Exception;

class Arg
{
    /**
     * Class dependencies.
     *
     * @var array
     */
    protected static $_classes = [
        'matcher' => 'Kahlan\Matcher'
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
     * The expected arguments.
     *
     * @var array
     */
    protected $_args = [];

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
     *                      - `'args'`    _string_ : the expected arcuments.
     */
    public function __construct($config = [])
    {
        $defaults = ['name' => '', 'not' => false, 'matchers' => [], 'args' => []];
        $config += $defaults;

        $this->_name     = $config['name'];
        $this->_not      = $config['not'];
        $this->_matchers = $config['matchers'];
        $this->_args     = $config['args'];
    }

    /**
     * Create an Argument Matcher
     *
     * @param  string  $name The name of the matcher.
     * @param  array   $args The arguments to pass to the matcher.
     * @return boolean
     */
    public static function __callStatic($name, $args)
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
            return new static(compact('name', 'matchers', 'not', 'args'));
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
        $args = $this->_args;
        array_unshift($args, $actual);
        $boolean = call_user_func_array($matcher . '::match', $args);
        return $this->_not ? !$boolean : $boolean;
    }
}
