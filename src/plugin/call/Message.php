<?php
/**
 * Kahlan: PHP Testing Framework
 *
 * @copyright     Copyright 2013, CrysaLEAD
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace kahlan\plugin\call;

class Message
{
    /**
     * Class dependencies.
     *
     * @var array
     */
    protected $_classes = [
        'arg' => 'kahlan\Arg'
    ];

    /**
     * Message name
     *
     * @var array
     */
    protected $_name = null;

    /**
     * Message params
     *
     * @var array
     */
    protected $_params = [];

    /**
     * Static call
     *
     * @var array
     */
    protected $_static = false;

    public function __construct($options = [])
    {
        $defaults = ['name' => null, 'params' => [], 'static' => false];
        $options += $defaults;

        $this->_name = $options['name'];
        $this->_params = $options['params'];
        $this->_static = $options['static'];
    }

    /**
     * Set params requirement.
     *
     * @param mixed <0,n> Parameter(s).
     */
    public function with()
    {
        $this->_params = func_get_args();
        return $this;
    }

    /**
     * Check if this message is compatible with passed call array.
     *
     * @param  array   $call A call array.
     * @return boolean
     */
    public function match($call)
    {
        if ($call['static'] !== $this->_static) {
            return false;
        }

        if ($call['name'] !== $this->_name) {
            return false;
        }

        if (!$this->matchParams($call['params'])) {
            return false;
        }
        return true;
    }

    /**
     * Check if this stub is compatible with passed args.
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

    public function getName()
    {
        return $this->_name;
    }

    public function getWith()
    {
        return $this->_params;
    }

    public function getStatic()
    {
        return $this->_static;
    }
}
