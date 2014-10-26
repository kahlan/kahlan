<?php
/**
 * Kahlan: PHP Testing Framework
 *
 * @copyright     Copyright 2013, CrysaLEAD
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace kahlan\cli;

class Args {

    protected $_args = [];

    protected $_options = [];

    public function __construct($options = []) {
        foreach ($options as $name => $config) {
            $this->option($name, $config);
        }
    }

    public function options() {
        return $this->_options;
    }

    public function option($name = null, $config = null, $value = null) {
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
        if (is_array($config)) {
            $config += $defaults;
            return $this->_options[$name] = $config;
        }
        $attribute = $config;
        $config = $this->option($name);
        return $this->_options[$name] = [$attribute => $value] + $config;
    }

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

    public function set($name, $value)
    {
        $this->_args[$name] = $value;
        return $this->_args;
    }

    public function add($name, $value)
    {
        $config = $this->option($name);
        if ($config['array']) {
            $this->_args[$name][] = $value;
        } else {
            $this->set($name, $value);
        }
        return $this->_args;
    }

    public function get($name = null)
    {
        if ($name === null) {
            $result = [];
            foreach ($this->_options as $key => $value) {
                if (isset($value['default'])) {
                    $result[$key] = $this->_get($key);
                }
            }
            foreach ($this->_args as $key => $value) {
                $result[$key] = $this->_get($key);
            }
            return $result;
        }
        return $this->_get($name);
    }

    protected function _get($name)
    {
        $config = $this->option($name);
        $value = isset($this->_args[$name]) ? $this->_args[$name] : $config['default'];
        return $this->cast($value, $config['type'], $config['array']);
    }

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
