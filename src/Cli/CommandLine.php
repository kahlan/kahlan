<?php declare(strict_types=1);

namespace Kahlan\Cli;

class CommandLine
{

    /**
     * Arguments attributes
     *
     * @var array
     */
    protected $_options = [];

    /**
     * Defaults options values
     *
     * @var array
     */
    protected $_defaults = [];

    /**
     * Arguments values.
     *
     * @var array
     */
    protected $_values = [];

    /**
     * @param array $options An array of option's attributes where keys are option's names
     *                       and values are an array of attributes.
     */
    public function __construct(array $options = [])
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
    public function options(): array
    {
        return $this->_options;
    }

    /**
     * Gets/Sets/Overrides an option's attributes.
     *
     * @param string $name The name of the option.
     * @param array|string $config The option attributes to set.
     * @param mixed $value
     * @return array
     */
    public function option(string $name, $config = [], $value = null): array
    {
        $defaults = [
            'type'    => 'string',
            'group'   => false,
            'array'   => false,
            'value'   => null,
            'default' => null
        ];
        if (func_num_args() === 1) {
            if (isset($this->_options[$name])) {
                return $this->_options[$name];
            }
            return $defaults;
        }
        $config = is_array($config) ? $config + $defaults : [$config => $value] + $this->option($name);

        $this->_options[$name] = $config;

        [$key, $extra] = $this->_splitOptionName($name);

        if ($extra) {
            $this->option($key, ['group' => true, 'array' => true]);
        }
        if ($config['default'] !== null) {
            $this->_defaults[$key][$extra] = $this->_get($name);
        }

        return $config;
    }

    /**
     * Parses a command line argv.
     *
     * @param  array   $argv     An argv data.
     * @param  boolean $override If set to `false` it doesn't override already setted data.
     * @return array             The parsed attributes
     */
    public function parse(array $argv, bool $override = true): array
    {
        $exists = [];
        if ($override) {
            $this->_values = $this->_defaults;
        } else {
            $exists = array_fill_keys(array_keys($this->_values), true);
        }

        foreach ($argv as $arg) {
            if ($arg === '--') {
                break;
            }
            if ($arg[0] === '-') {
                [$name, $value] = $this->_parse(ltrim($arg, '-'));
                if ($override || !isset($exists[$name])) {
                    $this->add($name, $value);
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
    protected function _parse(string $arg): array
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
     * Checks if an option has been set.
     *
     * @param  string  $name The name of the option.
     * @return bool
     */
    public function exists(string $name): bool
    {
        [$key, $extra] = $this->_splitOptionName($name);
        if (isset($this->_values[$key]) && is_array($this->_values[$key]) && array_key_exists($extra, $this->_values[$key])) {
            return true;
        }
        if (isset($this->_options[$name])) {
            return isset($this->_options[$name]['default']);
        }
        return false;
    }

    /**
     * Sets the value of a specific option.
     *
     * @param string $name  The name of the option.
     * @param mixed $value The value of the option to set.
     * @return mixed The set value.
     */
    public function set(string $name, $value)
    {
        [$key, $extra] = $this->_splitOptionName($name);
        if ($extra && !isset($this->_options[$key])) {
            $this->option($key, ['group' => true, 'array' => true]);
        }
        return $this->_values[$key][$extra] = $value;
    }

    /**
     * Adds a value to a specific option (or set if it's not an array).
     *
     * @param string $name The name of the option.
     * @param mixed $value The value of the option to set.
     * @return mixed The value.
     */
    public function add(string $name, $value)
    {
        $config = $this->option($name);
        [$key, $extra] = $this->_splitOptionName($name);

        if ($config['array']) {
            $this->_values[$key][$extra][] = $value;
        } else {
            $this->set($name, $value);
        }
        return $this->_values[$key][$extra];
    }

    /**
     * Gets the value of a specific option.
     *
     * @param string $name The name of the option.
     * @return mixed The value.
     */
    public function get(string $name = '')
    {
        if (func_num_args()) {
            return $this->_get($name);
        }
        $result = [];
        foreach ($this->_values as $key => $data) {
            foreach ($data as $extra => $value) {
                if ($extra === '') {
                    $result[$key] = $this->_get($key);
                } else {
                    $result[$key][$extra] = $this->_get($key . ':' . $extra);
                }
            }
        }
        return $result;
    }

    /**
     * Helper for `get()`.
     *
     * @param string $name The name of the option.
     * @return bool|int|string|array The casted value.
     */
    protected function _get(string $name)
    {
        $config = $this->option($name);
        [$key, $extra] = $this->_splitOptionName($name);

        if ($extra === '' && $config['group']) {
            $result = [];
            if (!isset($this->_values[$key])) {
                return $result;
            }
            foreach ($this->_values[$key] as $extra => $value) {
                $result[$extra] = $this->_get($name . ':' . $extra);
            }
            return $result;
        } else {
            $value = isset($this->_values[$key][$extra]) ? $this->_values[$key][$extra] : $config['default'];
        }

        $casted = $this->cast($value, $config['type'], $config['array']);
        if (!isset($config['value'])) {
            return $casted;
        }
        if (is_callable($config['value'])) {
            return array_key_exists($key, $this->_values) ? $config['value']($casted, $name, $this) : $casted;
        }
        return $config['value'];
    }

    /**
     * Casts a value according to the option attributes.
     *
     * @param mixed $value The value to cast.
     * @param string $type The type of the value.
     * @param bool $array If `true`, the argument value is considered to be an array.
     * @return bool|int|string|array The casted value.
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
            $value = $value !== null ? (int) $value + 0 : 1;
        } elseif ($type === 'string') {
            $value = ($value !== true && $value !== null) ? (string) $value : null;
        }
        if ($array) {
            return $value ? (array) $value : [];
        }
        return $value;
    }

    /**
     * Helper to split option name
     *
     * @param  string $name The option name.
     * @return array
     */
    protected function _splitOptionName(string $name): array
    {
        $parts = explode(':', $name, 2);
        return [$parts[0], $parts[1] ?? ''];
    }
}
