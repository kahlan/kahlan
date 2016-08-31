<?php
namespace Kahlan\Plugin\Call\Message;

class FunctionMessage extends Message
{
    /**
     * The Constructor.
     *
     * @param array $config Possible options are:
     *                       - `'name'`   _string_ : The function name.
     *                       - `'params'` _array_  : The function params.
     */
    public function __construct($config = [])
    {
        $defaults = ['name' => null, 'params' => null];
        $config += $defaults;

        $this->_name   = $config['name'];
        $this->_params = $config['params'];
    }

    /**
     * Checks if this message is compatible with passed call array.
     *
     * @param  array   $call A call array.
     * @return boolean
     */
    public function match($call)
    {
        return $call['name'] === $this->_name;
    }
}
