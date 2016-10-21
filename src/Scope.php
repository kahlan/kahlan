<?php
namespace Kahlan;

use Closure;
use Exception;
use Kahlan\SkipException;
use Kahlan\Suite;
use Kahlan\Given;

abstract class Scope
{
    /**
     * List of reserved keywords which can't be used as scope variable.
     *
     * @var array
     */
    protected static $_blacklist = [
        '__construct' => true,
        '__call'      => true,
        '__get'       => true,
        '__set'       => true,
        'given'       => true,
        'afterAll'    => true,
        'afterEach'   => true,
        'beforeAll'   => true,
        'beforeEach'  => true,
        'context'     => true,
        'describe'    => true,
        'expect'      => true,
        'given'       => true,
        'it'          => true,
        'fdescribe'   => true,
        'fcontext'    => true,
        'fit'         => true,
        'skipIf'      => true,
        'xdescribe'   => true,
        'xcontext'    => true,
        'xit'         => true
    ];

    /**
     * The block instance.
     *
     * @var Scope
     */
    protected $_block = null;

    /**
     * The parent scope.
     *
     * @var Scope
     */
    protected $_parent = null;

    /**
     * The scope's data.
     *
     * @var array
     */
    protected $_data = [];

    /**
     * The lazy loaded scope's data.
     *
     * @var array
     */
    protected $_given = [];

    /**
     * The Constructor.
     *
     * @param array $config The Scope config array. Options are:
     *                       -`'block'` _object_ : the block instance.
     */
    public function __construct($config = [])
    {
        $defaults = [
            'block' => null
        ];
        $config += $defaults;
        if (!$this->_block = $config['block']) {
            return;
        }
        if ($parent = $this->_block->parent()) {
            $this->_parent = $parent->scope();
        }
    }

    /**
     * Getter.
     *
     * @param  string $key The name of the variable.
     *
     * @return mixed  The value of the variable.
     */
    public function &__get($key)
    {
        if (array_key_exists($key, $this->_data)) {
            return $this->_data[$key];
        }
        if (array_key_exists($key, $this->_given)) {
            $scope = Suite::current()->scope();
            $scope->{$key} = $this->_given[$key]($scope);
            return $scope->__get($key);
        }
        if ($this->_parent !== null) {
            return $this->_parent->__get($key);
        }
        throw new Exception("Undefined variable `{$key}`.");
    }

    /**
     * Setter.
     *
     * @param  string $key   The name of the variable.
     * @param  mixed  $value The value of the variable.
     *
     * @return mixed  The value of the variable.
     */
    public function __set($key, $value)
    {
        if (isset(static::$_blacklist[$key])) {
            throw new Exception("Sorry `{$key}` is a reserved keyword, it can't be used as a scope variable.");
        }
        return $this->_data[$key] = $value;
    }

    /**
     * Allow closures assigned to the scope property to be inkovable.
     *
     * @param  string $name Name of the method being called.
     * @param  array  $args Enumerated array containing the passed arguments.
     *
     * @return mixed
     * @throws Throw an Exception if the property doesn't exists / is not callable.
     */
    public function __call($name, $args)
    {
        $property = null;
        $property = $this->__get($name);

        if (is_callable($property)) {
            return call_user_func_array($property, $args);
        }
        throw new Exception("Uncallable variable `{$name}`.");
    }

    /**
     * Sets a lazy loaded data.
     *
     * @param  string  $name    The lazy loaded variable name.
     * @param  Closure $closure The lazily executed closure.
     * @return object
     */
    public function given($name, $closure)
    {
        if (isset(static::$_blacklist[$name])) {
            throw new Exception("Sorry `{$name}` is a reserved keyword, it can't be used as a scope variable.");
        }

        $given = new Given($closure);
        if (array_key_exists($name, $this->_given)) {
            $given->{$name} = $this->_given[$name](Suite::current()->scope());
        }
        $this->_given[$name] = $given;
        return $this;
    }

    /**
     * Skips specs(s) if the condition is `true`.
     *
     * @param  boolean       $condition
     *
     * @return self
     * @throws SkipException
     */
    public function skipIf($condition)
    {
        $this->_block->skipIf($condition);
    }

    /**
     * Clear scope variables.
     */
    public function clear()
    {
        $this->_data = [];
    }

}
