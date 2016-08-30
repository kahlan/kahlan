<?php
namespace Kahlan\Plugin\Call\Message;

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
    protected $_params = [];

    /**
     * Number of occurences to match.
     *
     * @var integer
     */
    protected $_times = 0;

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
}
