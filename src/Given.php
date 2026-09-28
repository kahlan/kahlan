<?php
namespace Kahlan;

use Exception;

class Given
{
    /**
     * The parent scope.
     *
     * @var object
     */
    protected $_parent = null;

    /**
     * The scope's data.
     *
     * @var array
     */
    protected $_data = [];

    /**
     * The given value or a factory closure.
     *
     * @var mixed
     */
    protected $_closure = null;

    /**
     * The Constructor.
     *
     * @param  array $closure A closure.
     *
     * @throws Exception
     */
    public function __construct($closure)
    {
        $this->_closure = $closure;
        if (!is_callable($this->_closure)) {
            throw new Exception("A closure is required by `Given` constructor.");
        }
        // Static closures can't be bound (warning + `null`), keep them as is.
        set_error_handler(static fn() => true, E_WARNING);
        try {
            $this->_closure = $this->_closure->bindTo($this) ?? $this->_closure;
        } finally {
            restore_error_handler();
        }
    }

    /**
     * Returns the given data
     *
     * @return mixed
     */
    public function __invoke($parent = null)
    {
        $this->_parent = $parent;
        $closure = $this->_closure;
        return $closure();
    }

    /**
     * Getter.
     *
     * @param  string $key The name of the variable.
     *
     * @return mixed       The value of the variable.
     * @throws Exception
     */
    public function &__get($key)
    {
        if (array_key_exists($key, $this->_data)) {
            return $this->_data[$key];
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
     * @return mixed         The value of the variable.
     */
    public function __set($key, $value)
    {
        return $this->_data[$key] = $value;
    }
}
