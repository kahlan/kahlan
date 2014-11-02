<?php
namespace kahlan\reporter\coverage;

class Metrics
{
    /**
     * Reference to the parent metrics.
     *
     * @param Metrics
     */
    protected $_parent = null;

    /**
     * The string name reference of the metrics.
     *
     * @param string
     */
    protected $_name = '';

    /**
     * The type of the metrics is about.
     *
     * @param string
     */
    protected $_type = 'namespace';

    /**
     * The metrics data.
     *
     * @param array
     */
    protected $_metrics = [
        'loc' => 0,
        'ncloc' => 0,
        'covered' => 0,
        'eloc' => 0,
        'methods' => 0,
        'coveredMethods' => 0,
        'files' => []
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
     *              - `'name'`: the string name reference of the metrics.
     *              - `'type'`  : the type of the metrics is about.
     *              - `'parent'`: reference to the parent metrics.
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
            case 'class':
                $this->_name = $pname ? $pname . '\\' . $options['name'] : $options['name'];
            break;
            case 'method':
                $this->_name = $pname ? $pname . '::' . $options['name'] : $options['name'];
            break;
        }
    }

    /**
     * Retruns the parent reference.
     *
     * @return Metrics The parent reference
     */
    public function parent()
    {
        return $this->_parent;
    }

    /**
     * Retruns the name of the metrics.
     *
     * @return string The name of the metrics.
     */
    public function name()
    {
        return $this->_name;
    }

    /**
     * Retruns the type of the metrics.
     *
     * @return string The type of the metrics.
     */
    public function type()
    {
        return $this->_type;
    }

    /**
     * Retruns the metrics stats.
     *
     * @return array  The metrics data.
     */
    public function data($metrics = [])
    {
        if (func_num_args() === 0) {
            return $this->_metrics;
        }

        $this->_metrics = $metrics + $this->_metrics;

        if ($this->_metrics['eloc']) {
            $this->_metrics['percent'] = ($this->_metrics['covered'] * 100) / $this->_metrics['eloc'];
        } else {
            $this->_metrics['percent'] = 0;
        }
    }

    /**
     * Add some metrics to the current metrics.
     *
     * @param string  The name reference of the metrics.
     * @param Metrics The metrics instance.
     */
    public function add($name, $metrics)
    {
        if (!$name) {
            return $this->data($metrics);
        }
        list($name, $subname, $type) = $this->_parseName($name);

        if (!isset($this->_childs[$name])) {
            $parent = $this;
            $this->_childs[$name] = new Metrics(compact('name', 'type', 'parent'));
        }
        $this->_merge($metrics);
        $this->_childs[$name]->add($subname, $metrics);
    }

    /**
     * Get the metrics from a name.
     *
     * @param  string The name reference of the metrics.
     * @return object The metrics instance.
     */
    public function get($name = null)
    {
        if (!$name) {
            return $this;
        }
        list($name, $subname, $type) = $this->_parseName($name);

        if (!isset($this->_childs[$name])) {
            return null;
        }
        return $this->_childs[$name]->get($subname);
    }

    /**
     * Get the childs of the current metrics.
     *
     * @param  string The name reference of the metrics.
     * @return array  The metrics childs.
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
     * Retruns meta info of a metrics from a name reference..
     *
     * @param  string The name reference of the metrics.
     * @return array  The meta info of a metrics.
     */
    protected function _parseName($name)
    {
        $subname = null;
        if (strpos($name, '\\') !== false) {
            $type = 'namespace';
            list($name, $subname) = explode('\\', $name, 2);
        } elseif (strpos($name, '::') !== false) {
            $type = 'class';
            list($name, $subname) = explode('::', $name, 2);
        }
        if (!$subname) {
            $type = $this->_type === 'class' ? 'method' : 'function';
        }
        return [$name, $subname, $type];
    }

    /**
     * Merge some given metrics to the existing metrics .
     *
     * @param array Some metrics.
     */
    protected function _merge($metrics = [])
    {
        foreach (['loc', 'ncloc', 'covered', 'eloc', 'methods', 'coveredMethods'] as $name) {
            if (!isset($metrics[$name])) {
                continue;
            }
            $metrics[$name] += $this->_metrics[$name];
        }

        if (isset($metrics['files'])) {
            $metrics['files'] = array_merge($this->_metrics['files'], $metrics['files']);
            $metrics['files'] = array_unique($metrics['files']);
        }
        unset($metrics['line']);
        $this->data($metrics);
    }
}
