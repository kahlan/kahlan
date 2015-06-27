<?php
namespace kahlan\plugin\stub;

use Exception;

class Method extends \kahlan\plugin\call\Message
{
    /**
     * Index value in the `Method::$_returns` array.
     *
     * @var array
     */
    protected $_index = 0;

    /**
     * Stub implementation.
     *
     * @var Closure
     */
    protected $_closure = null;

    /**
     * Return values.
     *
     * @var array
     */
    protected $_returns = [];

    /**
     * The Constructor.
     *
     * @param array $config The options array, possible options are:
     *                      - `'closure'`: the closure to execute for this stub.
     *                      - `'params'`: the params required for exectuting this stub.
     *                      - `'static'`: the type of call required for exectuting this stub.
     *                      - `'returns'`: the returns values for this stub (used only if
     *                        the `'closure'` option is missing).
     */
    public function __construct($config = [])
    {
        $defaults = ['closure' => null, 'params' => [], 'returns' => [], 'static' => false];
        $config += $defaults;

        parent::__construct($config);
        $this->_closure = $config['closure'];
        $this->_returns = $config['returns'];
    }

    /**
     * Runs the stub.
     *
     * @param  string $self   The context form which the stub need to be executed.
     * @param  array  $params The call parameters array.
     * @return mixed          The returned stub result.
     */
    public function __invoke($self, $params)
    {
        if ($this->_closure) {
            if (is_string($self)) {
                $closure = $this->_closure->bindTo(null, $self);
            } else {
                $closure = $this->_closure->bindTo($self, get_class($self));
            }
            return call_user_func_array($closure, $params);
        }
        if (isset($this->_returns[$this->_index])) {
            return $this->_returns[$this->_index++];
        }
        return $this->_returns ? end($this->_returns) : null;
    }

    /**
     * Sets the stub logic.
     *
     * @param Closure $closure The logic.
     */
    public function run($closure)
    {
        if ($this->_returns) {
            throw new Exception("Some return values are already set.");
        }
        if (!is_callable($closure)) {
            throw new Exception("The passed parameter is not callable.");
        }
        $this->_closure = $closure;
    }

    /**
     * Set. return values.
     *
     * @param mixed ... <0,n> Return value(s).
     */
    public function andReturn()
    {
        if ($this->_closure) {
            throw new Exception("Closure already set.");
        }
        if (func_num_args()) {
            $this->_returns = func_get_args();
        }
    }

}
