<?php
/**
 * Kahlan: PHP Testing Framework
 *
 * @copyright     Copyright 2013, CrysaLEAD
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace kahlan\cli;

use Exception;

class Args {

    /**
     * Options attributes
     *
     * @var array
     */
    protected $_options = [];

    /**
     * Options values
     *
     * @var array
     */
    protected $_values = [];

    /**
     * The Constructor.
     *
     * @param array $options An array of options attributes where keys are option names
     *                       and values are an array of attributes.
     */
    public function __construct($options = [])
    {
        foreach ($options as $name => $config) {
            $this->option($name, $config);
        }
    }

    /**
     * Returns all options attributes.
     *
     * @return array
     */
    public function options() {
        return $this->_options;
    }


    /**
     * Get/Set an option attributes.
     *
     * @param  string $name   The name of the option.
     * @param  array  $config The option attributes. If not passed, acts as a getter.
     * @return array
     */
    public function option($name = null, $config = [])
    {
        $defaults = [
            'type'    => 'string',
            'array'   => false,
            'default' => null
        ];
        if (func_num_args() === 1) {
            if (isset($this->_options[$name])) {
                return $this->_options[$name];
            }
            return $defaults;
        }
        $config += $defaults;
        return $this->_options[$name] = $config;
    }

    /**
     * Override/Create an option attribute.
     *
     * @param  string $name      The name of the option.
     * @param  string $attribute The name attribute.
     * @param  mixed  $value     The value of the attribute to set.
     * @return array
     */
    public function attribute($name = null, $attribute = null, $value = null)
    {
        $config = $this->option($name);
        return $this->_options[$name] = [$attribute => $value] + $config;
    }

    /**
     * Parse a command line argv.
     *
     * @param  array $argv An argv data.
     * @return array       The parsed attributes
     */
    public function parse($argv)
    {
        foreach($argv as $arg) {
            if ($arg === '--') {
                break;
            }
            if ($arg[0] === '-') {
                list($name, $value) = $this->_parse(ltrim($arg,'-'));
                $this->add($name, $value);
            }
        }
        return $this->get();
    }

    /**
     * Helper for `parse()`
     *
     * @param  string $arg A string option.
     * @return array       The parsed option
     */
    protected function _parse($arg)
    {
        $pos = strpos($arg, '=');
        if ($pos !== false) {
            $name = substr($arg, 0, $pos);
            $value = substr($arg, $pos + 1);
        } else {
            $name = $arg;
            $value = '';
        }
        return [$name, $value];
    }

    /**
     * Set the value of a specific option.
     *
     * @param  string $name      The name of the option.
     * @param  mixed  $value     The value of the option to set.
     * @return array             The setted value.
     */
    public function set($name, $value)
    {
        return $this->_values[$name] = $value;
    }

    /**
     * Add a value to a specific option (or set if it's not an array).
     *
     * @param  string $name      The name of the option.
     * @param  mixed  $value     The value of the option to set.
     * @return array             The setted value.
     */
    public function add($name, $value)
    {
        $config = $this->option($name);
        if ($config['array']) {
            $this->_values[$name][] = $value;
        } else {
            $this->set($name, $value);
        }
        return $this->_values[$name];
    }

    /**
     * Get the value of a specific option.
     *
     * @param  string $name      The name of the option.
     * @return array             The value.
     */
    public function get($name = null)
    {
        if ($name === null) {
            $result = [];
            foreach ($this->_options as $key => $value) {
                if (isset($value['default'])) {
                    $result[$key] = $this->_get($key);
                }
            }
            foreach ($this->_values as $key => $value) {
                $result[$key] = $this->_get($key);
            }
            return $result;
        }
        return $this->_get($name);
    }

    /**
     * Helper for `get()`.
     *
     * @param  string $name The name of the option.
     * @return array        The casted value.
     */
    protected function _get($name)
    {
        $config = $this->option($name);
        $value = isset($this->_values[$name]) ? $this->_values[$name] : $config['default'];
        return $this->cast($value, $config['type'], $config['array']);
    }

    /**
     * Casts a value according to the option attributes.
     *
     * @param  string  $value The value to cast.
     * @param  string  $type  The type of the value.
     * @param  boolean $array If `true`, the option value is considered to be an array.
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
            $value = $value !== '' ? $value + 0 : 1;
        }
        if ($array) {
            return $value ? (array) $value : [];
        }
        return $value;
    }
}
