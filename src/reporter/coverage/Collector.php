<?php
/**
 * Kahlan: PHP Testing Framework
 *
 * @copyright     Copyright 2013, CrysaLEAD
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace kahlan\reporter\coverage;

use dir\Dir;
use kahlan\jit\Interceptor;

class Collector
{
    /**
     * Stack of active collectors.
     *
     * @var array
     */
    protected static $_collectors = [];

    /**
     * Class dependencies.
     *
     * @var array
     */
    protected $_classes = [
        'parser' => 'kahlan\analysis\Parser',
    ];

    /**
     * The driver instance which will log the coverage data.
     *
     * @var object
     */
    protected $_driver = null;

    /**
     * The path(s) which contain the code source files.
     *
     * @var array
     */
    protected $_paths = [];

    /**
     * Some prefix to remove to get the real file path.
     *
     * @var string
     */
    protected $_prefix = '';

    /**
     * The files presents in `Collector::_paths`.
     *
     * @var array
     */
    protected $_files = [];

    /**
     * The coverage data.
     *
     * @var array
     */
    protected $_coverage = [];

    /**
     * The metrics.
     *
     * @var array
     */
    protected $_metrics = [];

    /**
     * Cache all parsed files
     *
     * @see kahlan\reporter\coverage\Collector::_parse()
     * @var array
     */
    protected $_tree = [];

    /**
     * Constructor.
     *
     * @param array $options Possible options values are:
     *              - `'driver'`: the driver instance which will log the coverage data.
     *              - `'path'`  : the path(s) which contain the code source files.
     *              - `'prefix'`: some prefix to remove to get the real file path.
     */
    public function __construct($options = [])
    {
        $defaults = [
            'driver' => null,
            'path' => [],
            'prefix' => rtrim(Interceptor::instance()->cache(), DS)
        ];
        $options += $defaults;

        $this->_driver = $options['driver'];
        $this->_paths = (array) $options['path'];
        $this->_prefix = $options['prefix'];

        $files = Dir::scan([
            'path' => $this->_paths,
            'include' => '*.php',
            'type' => 'file'
        ]);
        foreach ($files as $file) {
            $this->_coverage[realpath($file)] = [];
        }
    }

    /**
     * Start collecting coverage data.
     *
     * @return boolean
     */
    public function start()
    {
        if ($collector = end(static::$_collectors)) {
            $collector->add($collector->_driver->stop());
        }
        static::$_collectors[] = $this;
        $this->_driver->start();
        return true;
    }

    /**
     * Stop collecting coverage data.
     *
     * @return boolean
     */
    public function stop($mergeToParent = true)
    {
        $collector = end(static::$_collectors);
        $collected = [];
        if ($collector !== $this) {
            return false;
        }
        array_pop(static::$_collectors);
        $collected = $this->_driver->stop();
        $this->add($collected);

        $collector = end(static::$_collectors);
        if (!$collector) {
            return true;
        }
        $collector->add($mergeToParent ? $collected : []);
        $collector->_driver->start();
        return true;
    }

    /**
     * Add some coverage data to the collector.
     *
     * @param  array $coverage Some coverage data.
     * @return array The current coverage data.
     */
    public function add($coverage)
    {
        if (!$coverage) {
            return;
        }
        foreach ($coverage as $file => $data) {
            $this->addFile($file, $data);
        }
        return $this->_coverage;
    }

    /**
     * Add some coverage data to the collector.
     *
     * @param  string $file     A file path.
     * @param  array  $coverage Some coverage related to the file path.
     */
    public function addFile($file, $coverage)
    {
        $prefix = $this->_prefix;
        $file = preg_replace("~^{$prefix}~", '', $file);
        if (preg_match("/eval\(\)'d code$/", $file) || !isset($this->_coverage[$file])) {
            return;
        }
        foreach ($coverage as $line => $value) {
            if (!isset($this->_coverage[$file][$line])) {
                $this->_coverage[$file][$line] = $value;
            } else {
                $this->_coverage[$file][$line] += $value;
            }
        }
    }

    /**
     * Return coverage data.
     *
     * @return array The coverage data.
     */
    public function export()
    {
        return $this->_coverage;
    }

    /**
     * Return the collected metrics from coverage data.
     *
     * @return Metrics The collected metrics.
     */
    public function metrics()
    {
        $this->_metrics = new Metrics();
        foreach ($this->_coverage as $file => $coverage) {
            $node = $this->_parse($file);
            $this->_processTree($file, $node, $node->tree, $coverage);
        }
        return $this->_metrics;
    }

    /**
     * Helper for `Collector::metrics()`.
     *
     * @param  string  $file     The processed file.
     * @param  NodeDef $root     The root node of the processed file.
     * @param  NodeDef $nodes    The nodes to collect metrics on.
     * @param  array   $coverage The coverage data.
     * @param  string  $path     The naming of the processed node.
     */
    protected function _processTree($file, $root, $nodes, $coverage, $path = '')
    {
        foreach ($nodes as $node) {
            $this->_processNode($file, $root, $node, $coverage, $path);
        }
    }

    /**
     * Helper for `Collector::metrics()`.
     *
     * @param  string  $file     The processed file.
     * @param  NodeDef $root     The root node of the processed file.
     * @param  NodeDef $node     The node to collect metrics on.
     * @param  array   $coverage The coverage data.
     * @param  string  $path     The naming of the processed node.
     */
    protected function _processNode($file, $root, $node, $coverage, $path)
    {
        if ($node->type === 'class' || $node->type === 'namespace') {
            $path = "{$path}\\" . $node->name;
            $this->_processTree($file, $root, $node->tree, $coverage, $path);
        } elseif ($node->type === 'function' && !$node->isClosure) {
            $metrics = $this->_processMethod($file, $root, $node, $coverage);
            $prefix = $node->isMethod ? "{$path}::" : "{$path}\\";
            $this->_metrics->add(ltrim($prefix . $node->name . '()', '\\'), $metrics);
        } elseif (count($node->tree)) {
            $this->_processTree($file, $root, $node->tree, $coverage, $path);
        }
    }

    /**
     * Helper for `Collector::metrics()`.
     *
     * @param  string  $file     The processed file.
     * @param  NodeDef $root     The root node of the processed file.
     * @param  NodeDef $node     The node to collect metrics on.
     * @param  array   $coverage The coverage data.
     * @return array   The collected metrics.
     */
    protected function _processMethod($file, $root, $node, $coverage)
    {
        $metrics = [
            'loc' => 0,
            'ncloc' => 0,
            'covered' => 0,
            'eloc' => 0,
            'percent' => 0,
            'methods' => 1,
            'coveredMethods' => 0
        ];
        if ($node->type !== 'function') {
            continue;
        }
        for ($line = $node->lines['start']; $line <= $node->lines['stop']; $line++) {
            $this->_processLine($line, $coverage, $metrics);
        }
        $metrics['file'] = $file;
        $metrics['line'] = $node->lines['start'];
        $metrics['loc'] = ($node->lines['stop'] - $node->lines['start']) + 1;
        $metrics['eloc'] = $metrics['loc'] - $metrics['ncloc'];
        if ($metrics['covered']) {
            $metrics['coveredMethods'] = 1;
        }
        return $metrics;
    }

    /**
     * Helper for `Collector::metrics()`.
     *
     * @param int   $line     The line number to collect.
     * @param array $coverage The coverage data.
     * @param array $metrics  The output metrics array.
     */
    protected function _processLine($line, $coverage, &$metrics)
    {
        if (!$coverage) {
            return;
        }
        if (!isset($coverage[$line])) {
            $metrics['ncloc']++;
        } elseif ($coverage[$line]) {
            $metrics['covered']++;
        }
    }

    /**
     * Retruns & cache the tree structure of a file.
     *
     * @param string $file the file path to use for building the tree structure.
     */
    protected function _parse($file)
    {
        if (isset($this->_tree[$file])) {
            return $this->_tree[$file];
        }
        $parser = $this->_classes['parser'];
        return $this->_tree[$file] = $parser::parse(file_get_contents($file), true);
    }
}
