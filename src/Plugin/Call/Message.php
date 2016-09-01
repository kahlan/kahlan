<?php
namespace Kahlan\Plugin\Call;

class Message
{
    /**
     * Class dependencies.
     *
     * @var array
     */
    protected $_classes = [
        'arg' => 'Kahlan\Arg'
    ];

    /**
     * Message name.
     *
     * @var array
     */
    protected $_name = null;

    /**
     * Message params.
     *
     * @var array
     */
    protected $_params = null;

    /**
     * Number of occurences to match.
     *
     * @var integer
     */
    protected $_times = 0;

    /**
     * Static call.
     *
     * @var array
     */
    protected $_static = false;

    /**
     * The Constructor.
     *
     * @param array $config Possible options are:
     *                       - `'name'`   _string_ : The method name.
     *                       - `'params'` _array_  : The method params.
     *                       - `'static'` _boolean_: `true` if the method is static, `false` otherwise.
     */
    public function __construct($config = [])
    {
        $defaults = [
            'reference' => null,
            'name' => null,
            'params' => null,
            'static' => false
        ];
        $config += $defaults;

        $this->_reference = $config['reference'];
        $this->_name = $config['name'];
        $this->_params = $config['params'];
        $this->_static = $config['static'];
    }

    /**
     * Sets params requirement.
     *
     * @param  mixed ... <0,n> Parameter(s).
     * @return self
     */
    public function with()
    {
        $this->_params = func_get_args();
        return $this;
    }

    /**
     * Sets the number of occurences.
     *
     * @return object $this.
     */
    public function once()
    {
        return $this->times(1);
    }

    /**
     * Gets/sets the number of occurences.
     *
     * @param  integer $times The number of occurences to set or none to get it.
     * @return mixed          The number of occurences on get or `$this` otherwise.
     */
    public function times($times = null)
    {
        if (!func_num_args()) {
            return $this->_times;
        }
        $this->_times = $times;
        return $this;
    }

    /**
     * Checks if this message is compatible with passed call array.
     *
     * @param  array   $call       A call array.
     * @param  boolean $withParams Boolean indicating if matching should take parameters into account.
     * @return boolean
     */
    public function match($call, $withParams = true)
    {
        if (isset($call['static'])) {
            if ($call['static'] !== $this->_static) {
                return false;
            }
        }

        if ($call['name'] !== $this->_name) {
            return false;
        }

        if ($withParams) {
            return $this->matchParams($call['params']);
        }

        return true;
    }

    /**
     * Checks if this stub is compatible with passed args.
     *
     * @param  array   $params The passed args.
     * @return boolean
     */
    public function matchParams($params)
    {
        if (!$this->_params) {
            return true;
        }
        $arg = $this->_classes['arg'];
        foreach ($this->_params as $expected) {
            $actual = array_shift($params);
            if ($expected instanceof $arg) {
                if (!$expected->match($actual)) {
                    return false;
                }
            } elseif ($actual !== $expected) {
                return false;
            }
        }
        return true;
    }

    /**
     * Gets the method name.
     *
     * @return string
     */
    public function name()
    {
        return $this->_name;
    }

    /**
     * Gets the method params.
     *
     * @return array
     */
    public function params()
    {
        return $this->_params;
    }

    /**
     * Checks if the method is a static method.
     *
     * @return boolean
     */
    public function isStatic()
    {
        return $this->_static;
    }
}
