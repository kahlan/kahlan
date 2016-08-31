<?php
namespace Kahlan\Plugin\Call\Message;

class MethodMessage extends Message
{
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
        $defaults = ['name' => null, 'params' => null, 'static' => false];
        $config += $defaults;

        $this->_name   = $config['name'];
        $this->_params = $config['params'];
        $this->_static = $config['static'];
    }

    /**
     * Checks if this message is compatible with passed call array.
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

        return true;
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
