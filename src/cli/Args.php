<?php
namespace kahlan\cli;


class Args {

    /**
     * Arguments attributes
     *
     * @var array
     */
    protected $_arguments = [];

    /**
     * Arguments values.
     *
     * @var array
     */
    protected $_values = [];

    /**
     * The Constructor.
     *
     * @param array $arguments An array of argument's attributes where keys are argument's names
     *                         and values are an array of attributes.
     */
    public function __construct($arguments = [])
    {
        foreach ($arguments as $name => $config) {
            $this->argument($name, $config);
        }
    }

    /**
     * Returns all arguments attributes.
     *
     * @return array
     */
    public function arguments() {
        return $this->_arguments;
    }


    /**
     * Gets/Sets/Overrides an argument's attributes.
     *
     * @param  string $name   The name of the argument.
     * @param  array  $config The argument attributes to set.
     * @return array
     */
    public function argument($name = null, $config = [], $value = null)
    {
        $defaults = [
            'type'    => 'string',
            'array'   => false,
            'value'   => null,
            'default' => null
        ];
        if (func_num_args() === 1) {
            if (isset($this->_arguments[$name])) {
                return $this->_arguments[$name];
            }
            return $defaults;
        }
        $config = is_array($config) ? $config + $defaults : [$config => $value] + $this->argument($name);

        return $this->_arguments[$name] = $config;
    }

    /**
     * Parses a command line argv.
     *
     * @param  array   $argv     An argv data.
     * @param  boolean $override If set to `false` it doesn't override already setted data.
     * @return array             The parsed attributes
     */
    public function parse($argv, $override = true)
    {
        $exists = [];
        $override ? $this->_values = [] : $exists = array_fill_keys(array_keys($this->_values), true);

        foreach($argv as $arg) {
            if ($arg === '--') {
                break;
            }
            if ($arg[0] === '-') {
                list($name, $value) = $this->_parse(ltrim($arg,'-'));
                if ($override || !isset($exists[$name])) {
                    $this->add($name, $value, $override);
                }
            }
        }
        return $this->get();
    }

    /**
     * Helper for `parse()`.
     *
     * @param  string $arg A string argument.
     * @return array       The parsed argument
     */
    protected function _parse($arg)
    {
        $pos = strpos($arg, '=');
        if ($pos !== false) {
            $name = substr($arg, 0, $pos);
            $value = substr($arg, $pos + 1);
        } else {
            $name = $arg;
            $value = true;
        }
        return [$name, $value];
    }

    /**
     * Checks if an argument has been setted the value of a specific argument.
     *
     * @param  string  $name The name of the argument.
     * @return boolean
     */
    public function exists($name)
    {
        if (array_key_exists($name, $this->_values)) {
            return true;
        }
        if (isset($this->_arguments[$name])) {
            return isset($this->_arguments[$name]['default']);
        }
        return false;
    }

    /**
     * Sets the value of a specific argument.
     *
     * @param  string $name  The name of the argument.
     * @param  mixed  $value The value of the argument to set.
     * @return array         The setted value.
     */
    public function set($name, $value)
    {
        return $this->_values[$name] = $value;
    }

    /**
     * Adds a value to a specific argument (or set if it's not an array).
     *
     * @param  string $name  The name of the argument.
     * @param  mixed  $value The value of the argument to set.
     * @return array         The setted value.
     */
    public function add($name, $value)
    {
        $config = $this->argument($name);
        if ($config['array']) {
            $this->_values[$name][] = $value;
        } else {
            $this->set($name, $value);
        }
        return $this->_values[$name];
    }

    /**
     * Gets the value of a specific argument.
     *
     * @param  string $name The name of the argument.
     * @return array        The value.
     */
    public function get($name = null)
    {
        if ($name !== null) {
            return $this->_get($name);
        }
        $result = [];
        foreach ($this->_arguments as $key => $value) {
            if (isset($value['default'])) {
                $result[$key] = $this->_get($key);
            }
        }
        foreach ($this->_values as $key => $value) {
            $result[$key] = $this->_get($key);
        }
        return $result;
    }

    /**
     * Helper for `get()`.
     *
     * @param  string $name The name of the argument.
     * @return array        The casted value.
     */
    protected function _get($name)
    {
        $config = $this->argument($name);
        $value = isset($this->_values[$name]) ? $this->_values[$name] : $config['default'];
        $casted = $this->cast($value, $config['type'], $config['array']);
        if (!isset($config['value'])) {
            return $casted;
        }
        if (is_callable($config['value'])) {
            return array_key_exists($name, $this->_values) ? $config['value']($casted, $name, $this) : $casted;
        }
        return $config['value'];
    }

    /**
     * Casts a value according to the argument attributes.
     *
     * @param  string  $value The value to cast.
     * @param  string  $type  The type of the value.
     * @param  boolean $array If `true`, the argument value is considered to be an array.
     * @return array          The casted value.
     */
    public function cast($value, $type, $array = false)
    {
        if (is_array($value)) {
            $result = [];
            foreach ($value as $key => $item) {
                $result[$key] = $this->cast($item, $type);
            }
            return $result;
        }
        if ($type === 'boolean') {
            $value = ($value === 'false' || $value === '0' || $value === false || $value === null) ? false : true;
        } elseif ($type === 'numeric') {
            $value = $value !== null ? $value + 0 : 1;
        } elseif ($type === 'string') {
            $value = ($value !== true && $value !== null) ? (string) $value : null;
        }
        if ($array) {
            return $value ? (array) $value : [];
        }
        return $value;
    }

}
