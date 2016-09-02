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
     * Message arguments.
     *
     * @var array
     */
    protected $_args = null;

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
     *                       - `'name'`   _string_ : The message name.
     *                       - `'args'`   _array_  : The message arguments.
     *                       - `'static'` _boolean_: `true` if the method is static, `false` otherwise.
     */
    public function __construct($config = [])
    {
        $defaults = [
            'reference' => null,
            'name' => null,
            'args' => null,
            'static' => false
        ];
        $config += $defaults;

        $this->_reference = $config['reference'];
        $this->_name = $config['name'];
        $this->_args = $config['args'];
        $this->_static = $config['static'];
    }

    /**
     * Sets arguments requirement.
     *
     * @param  mixed ... <0,n> Argument(s).
     * @return self
     */
    public function with()
    {
        $this->_args = func_get_args();
        return $this;
    }

    /**
     * Checks if this message is compatible with passed call array.
     *
     * @param  array   $call     A call array.
     * @param  boolean $withArgs Boolean indicating if matching should take arguments into account.
     * @return boolean
     */
    public function match($call, $withArgs = true)
    {
        if (isset($call['static'])) {
            if ($call['static'] !== $this->_static) {
                return false;
            }
        }

        if ($call['name'] !== $this->_name) {
            return false;
        }

        if ($withArgs) {
            return $this->matchArgs($call['args']);
        }

        return true;
    }

    /**
     * Checks if this stub is compatible with passed args.
     *
     * @param  array   $args The passed arguments.
     * @return boolean
     */
    public function matchArgs($args)
    {
        if (!$this->_args) {
            return true;
        }
        $arg = $this->_classes['arg'];
        foreach ($this->_args as $expected) {
            $actual = array_shift($args);
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
     * Gets message name.
     *
     * @return string
     */
    public function name()
    {
        return $this->_name;
    }

    /**
     * Gets message arguments.
     *
     * @return array
     */
    public function args()
    {
        return $this->_args;
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
