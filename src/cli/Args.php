<?php
/**
 * Kahlan: PHP Testing Framework
 *
 * @copyright     Copyright 2013, CrysaLEAD
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace kahlan\cli;

class Args {

    protected $_defaults = [];

    protected $_args = [];

    protected $_types = [];

    public function __construct($types = []) {
        $this->_types = $types;
    }

    public function defaults($defaults = []) {
        $this->_defaults = $defaults;
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
        $type = $this->type($name);
        if ($type['array']) {
            $this->_args[$name][] = $value;
        } else {
            $this->_args[$name] = $value;
        }
        return $this->_args;
    }

    public function get($name = null)
    {
        if ($name === null) {
            $result = [];
            foreach ($this->_args as $key => $value) {
                $result[$key] = $this->_get($key);
            }
            foreach ($this->_defaults as $key => $value) {
                if (!isset($result[$key])) {
                    $result[$key] = $this->_get($key);
                }
            }
            return $result;
        }
        return $this->_get($name);
    }

    protected function _get($name)
    {
        if (!isset($this->_args[$name])) {
            if (!isset($this->_defaults[$name])) {
                return;
            }
            $value = $this->_defaults[$name];
        } else {
            $value = $this->_args[$name];
        }

        $type = $this->type($name);
        return $this->cast($value, $type['type'], $type['array']);
    }

    public function type($name) {
        $type = [
            'type'  => 'string',
            'array' => false
        ];

        if (isset($this->_types[$name])) {
            $type = $this->_types[$name] + $type;
        }
        return $type;
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
            $value = ($value === 'false' || $value === '0') ? false : true;
        } elseif ($type === 'numeric') {
            $value = $value !== '' ? $value + 0 : 1;
        }
        if ($array) {
            return $value ? (array) $value : [];
        }
        return $value;
    }
}
