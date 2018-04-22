<?php
namespace Kahlan\Plugin\Stub;

use Closure;
use Exception;

class Method extends \Kahlan\Plugin\Call\Message
{
    /**
     * Index value in the `Method::$_substitutes` array.
     *
     * @var integer
     */
    protected $_substituteIndex = 0;

    /**
     * Return values.
     *
     * @var array
     */
    protected $_substitutes = null;

    /**
     * Index value in the `Method::$_returns/Method::$_closures` array.
     *
     * @var integer
     */
    protected $_returnIndex = 0;

    /**
     * Stub implementation.
     *
     * @var Closure
     */
    protected $_closures = [];

    /**
     * Return values.
     *
     * @var array
     */
    protected $_returns = [];

    /**
     * The method return value.
     *
     * @var mixed
     */
    protected $_return = null;

    /**
     * Boolean indicating if a method must act as a replacement.
     *
     * @var array
     */
    protected $_actsAsAReplacement = false;

    /**
     * The Constructor.
     *
     * @param array $config The options array, possible options are:
     *                      - `'closures'`: the closures to execute for this stub.
     *                      - `'args'`:    the arguments required for exectuting this stub.
     *                      - `'static'`:  the type of call required for exectuting this stub.
     *                      - `'returns'`: the returns values for this stub (used only if
     *                        the `'closure'` option is missing).
     */
    public function __construct($config = [])
    {
        $defaults = ['closures' => [], 'args' => null, 'returns' => [], 'static' => false];
        $config += $defaults;

        parent::__construct($config);
        $this->_name = ltrim($this->_name, '\\');
        $this->_closures = (array) $config['closures'];
        $this->_returns = (array) $config['returns'];
    }

    /**
     * Runs the stub.
     *
     * @param  string $self   The context from which the stub need to be executed.
     * @param  array  $args   The call arguments array.
     * @return mixed          The returned stub result.
     */
    public function __invoke($args = [], $self = null)
    {
        if ($this->_closures) {
            if (isset($this->_closures[$this->_returnIndex])) {
                $closure = $this->_closures[$this->_returnIndex++];
            } else {
                $closure = end($this->_closures);
            }
            if (is_string($self)) {
                $closure = $closure->bindTo(null, $self);
            } elseif ($self) {
                $closure = $closure->bindTo($self, get_class($self));
            }
            $this->_return = call_user_func_array($closure, $args);
        } elseif (array_key_exists($this->_returnIndex, $this->_returns)) {
            $this->_return = $this->_returns[$this->_returnIndex++];
        } else {
            $this->_return = $this->_returns ? end($this->_returns) : null;
        }
        return $this->_return;
    }

    /**
     * Return a compatible callable.
     *
     * @return callable The callable.
     */
    public function closure()
    {
        if ($this->_closures) {
            if (isset($this->_closures[$this->_returnIndex])) {
                $closure = $this->_closures[$this->_returnIndex++];
            } else {
                $closure = end($this->_closures);
            }
            return $closure;
        } elseif (array_key_exists($this->_returnIndex, $this->_returns)) {
            $this->_return = $this->_returns[$this->_returnIndex++];
        } else {
            $this->_return = $this->_returns ? end($this->_returns) : null;
        }
        return function () {
            return $this->_return;
        };
    }

    /**
     * Set return values.
     *
     * @param mixed ... <0,n> Return value(s).
     */
    public function toBe()
    {
        if ($this->reference()) {
            $this->_substitutes = func_get_args();
        } else {
            $this->_actsAsAReplacement = true;
            if (func_num_args() !== 1) {
                throw new Exception("Only one hard method substitution is allowed through `toBe()`.");
            }
            $this->_closures = func_get_args();
        }
    }

    /**
     * Set the stub logic.
     */
    public function andRun()
    {
        if ($this->_returns) {
            throw new Exception("Some return value(s) has already been set.");
        }
        $closures = func_get_args();
        foreach ($closures as $closure) {
            if (!is_callable($closure)) {
                throw new Exception("The passed parameter is not callable.");
            }
        }
        $this->_closures = $closures;
    }

    /**
     * Set return values.
     *
     * @param mixed ... <0,n> Return value(s).
     */
    public function andReturn()
    {
        if ($this->_closures) {
            throw new Exception("Some closure(s) has already been set.");
        }
        $this->_returns = func_get_args();
    }

    /**
     * Get the actual return value.
     *
     * @return mixed
     */
    public function actualReturn()
    {
        return $this->_return;
    }


    /**
     * Get the method substitute.
     *
     * @return mixed
     */
    public function substitute()
    {
        if (isset($this->_substitutes[$this->_substituteIndex])) {
            return $this->_substitutes[$this->_substituteIndex++];
        }
        return $this->_substitutes ? end($this->_substitutes) : null;
    }

    /**
     * Check if the stub is a lazy stub
     *
     * @return boolean
     */
    public function actsAsAReplacement()
    {
        return $this->_actsAsAReplacement;
    }
}
