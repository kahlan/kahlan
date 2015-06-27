<?php
namespace kahlan\reporter\coverage;

class Metrics
{
    /**
     * Reference to the parent metrics.
     *
     * @var object
     */
    protected $_parent = null;

    /**
     * The string name reference of the metrics.
     *
     * @var string
     */
    protected $_name = '';

    /**
     * The type of the metrics is about.
     *
     * @var string
     */
    protected $_type = 'namespace';

    /**
     * The metrics data.
     *
     * @var array The metrics:
     *            - `'loc'`      _integer_ : the number of line of code.
     *            - `'lloc'`     _integer_ : the number of logical line of code (i.e code statements or those lines which end in a semicolon)
     *            - `'nlloc'`    _integer_ : the number of non logical line of code (i.e uncoverable).
     *            - `'cloc'`     _integer_ : the number of covered line of code
     *            - `'methods'`  _integer_ : the number of methods.
     *            - `'cmethods'` _integer_ : the number of covered methods.
     *            - `'files'`    _array_   : the file paths.
     */
    protected $_metrics = [
        'loc'      => 0,
        'lloc'     => 0,
        'nlloc'    => 0,
        'cloc'     => 0,
        'coverage' => 0,
        'methods'  => 0,
        'cmethods' => 0,
        'files'    => [],
        'percent'  => 0
    ];

    /**
     * The child metrics of the current metrics.
     *
     * @param array
     */
    protected $_childs = [];

    /**
     * Constructor
     *
      * @param array $options Possible options values are:
     *                        - `'name'`   _string_  : the string name reference of the metrics.
     *                        - `'type'`   _string_  : the type of the metrics is about.
     *                        - `'parent'` _instance_: reference to the parent metrics.
     */
    public function __construct($options = [])
    {
        $defaults = ['name' => '', 'type' => 'namespace', 'parent' => null];
        $options += $defaults;

        $this->_parent = $options['parent'];
        $this->_type = $options['type'];

        if (!$this->_parent) {
            $this->_name = $options['name'];
            return;
        }

        $pname =  $this->_parent->name();
        switch ($this->_type) {
            case 'namespace':
            case 'function':
            case 'trait':
            case 'class':
                $this->_name = $pname ? $pname . '\\' . $options['name'] : $options['name'];
            break;
            case 'method':
                $this->_name = $pname ? $pname . '::' . $options['name'] : $options['name'];
            break;
        }
    }

    /**
     * Gets the parent instance.
     *
     * @return object The parent instance
     */
    public function parent()
    {
        return $this->_parent;
    }

    /**
     * Gets the name of the metrics.
     *
     * @return string The name of the metrics.
     */
    public function name()
    {
        return $this->_name;
    }

    /**
     * Gets the type of the metrics.
     *
     * @return string The type of the metrics.
     */
    public function type()
    {
        return $this->_type;
    }

    /**
     * Gets/Sets the metrics stats.
     *
     * @param  array $metrics The metrics data to set if defined.
     * @return array          The metrics data.
     */
    public function data($metrics = [])
    {
        if (func_num_args() === 0) {
            return $this->_metrics;
        }

        $this->_metrics = $metrics + $this->_metrics;

        if ($this->_metrics['lloc']) {
            $this->_metrics['percent'] = ($this->_metrics['cloc'] * 100) / $this->_metrics['lloc'];
        } else {
            $this->_metrics['percent'] = 100;
        }
    }

    /**
     * Adds some metrics to the current metrics.
     *
     * @param string $name The name reference of the metrics.
     * @param string $type The type of metrics to add.
     *                     Possible values are: `'namespace'`, `'class' or 'function'.
     * @param array        The metrics array to add.
     */
    public function add($name, $type, $metrics)
    {
        if (!$name) {
            $this->_merge($metrics, true);
            return;
        }
        list($name, $subname, $nameType) = $this->_parseName($name, $type);
        if (!isset($this->_childs[$name])) {
            $parent = $this;
            $this->_childs[$name] = new Metrics([
                'name'   => $name,
                'parent' => $parent,
                'type'   => $nameType
            ]);
        }
        ksort($this->_childs);
        $this->_merge($metrics);
        $this->_childs[$name]->add($subname, $type, $metrics);
    }

    /**
     * Gets the metrics from a name.
     *
     * @param  string $name The name reference of the metrics.
     * @return object       The metrics instance.
     */
    public function get($name = null)
    {
        if (!$name) {
            return $this;
        }
        list($name, $subname, $type) = $this->_parseName($name);

        if (!isset($this->_childs[$name])) {
            return;
        }
        return $this->_childs[$name]->get($subname);
    }

    /**
     * Gets the childs of the current metrics.
     *
     * @param  string $name The name reference of the metrics.
     * @return array        The metrics childs.
     */
    public function childs($name = null)
    {
        if (!$name) {
            return $this->_childs;
        }
        list($name, $subname, $type) = $this->_parseName($name);

        if (!isset($this->_childs[$name])) {
            return null;
        }
        return $this->_childs[$name]->childs($subname);
    }

    /**
     * Gets meta info of a metrics from a name reference..
     *
     * @param  string $name The name reference of the metrics.
     * @param  string $type The type to use by default if not auto detected.
     * @return array        The parsed name.
     */
    protected function _parseName($name, $type = null)
    {
        $subname = '';
        if (strpos($name, '\\') !== false) {
            $type = 'namespace';
            list($name, $subname) = explode('\\', $name, 2);
        } elseif (strpos($name, '::') !== false) {
            $type = 'class';
            list($name, $subname) = explode('::', $name, 2);
        } elseif (preg_match('~\(\)$~', $name)) {
            $type = ($this->_type === 'class' || $this->_type === 'trait') ? 'method' : 'function';
        }
        return [$name, $subname, $type];
    }

    /**
     * Merges some given metrics to the existing metrics .
     *
     * @param array   $metrics Metrics data to merge.
     * @param boolean $line    Set to `true` for function only
     */
    protected function _merge($metrics = [], $line = false)
    {
        $defaults = [
            'loc'      => [],
            'nlloc'    => [],
            'lloc'     => [],
            'cloc'     => [],
            'files'    => [],
            'methods'  => 0,
            'cmethods' => 0
        ];
        $metrics += $defaults;

        foreach (['loc', 'nlloc', 'lloc', 'cloc', 'coverage', 'files', 'methods', 'cmethods'] as $name) {
            $metrics[$name] += $this->_metrics[$name];
        }
        if (!$line) {
            unset($metrics['line']);
        }
        $this->data($metrics);
    }
}
