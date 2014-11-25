<?php
namespace kahlan\reporter\coverage;

use dir\Dir;
use jit\Interceptor;

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
        'parser' => 'jit\Parser',
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
     * @var array
     */
    protected $_tree = [];

    /**
     * Temps cache of processed lines
     *
     * @var array
     */
    protected $_processed = [];

    /**
     * Constructor.
     *
     * @param array $options Possible options values are:
     *              - `'driver'`: the driver instance which will log the coverage data.
     *              - `'path'`  : the path(s) which contain the code source files.
     *              - `'base'`  : the base path of the repo (default: `getcwd`).
     *              - `'prefix'`: some prefix to remove to get the real file path.
     */
    public function __construct($options = [])
    {
        $defaults = [
            'driver'         => null,
            'path'           => [],
            'include'        => '*.php',
            'exclude'        => [],
            'type'           => 'file',
            'skipDots'       => true,
            'leavesOnly'     => false,
            'followSymlinks' => true,
            'recursive'      => true,
            'base'           => str_replace(DIRECTORY_SEPARATOR, '/', getcwd())
        ];
        $options += $defaults;

        if (Interceptor::instance()) {
            $options += ['prefix' => rtrim(Interceptor::instance()->cache(), DS)];
        } else {
            $options += ['prefix' => ''];
        }

        $this->_driver = $options['driver'];
        $this->_paths = (array) $options['path'];
        $this->_base = $options['base'];
        $this->_prefix = $options['prefix'];

        $files = Dir::scan($this->_paths, $options);
        foreach ($files as $file) {
            $this->_coverage[realpath($file)] = [];
        }
    }

    /**
     * Return the used driver.
     *
     * @return object
     */
    public function driver() {
        return $this->_driver;
    }

    /**
     * Return the base path used to compute relative paths.
     *
     * @return string
     */
    public function base() {
        return rtrim($this->_base, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
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
        $file = $this->realpath($file);
        if (!$this->collectable($file)) {
            return;
        }
        $nbLines = count(file($file));

        foreach ($coverage as $line => $value) {
            if ($line === 0 || $line >= $nbLines) {
                continue; // Because Xdebug bugs...
            }
            if (!isset($this->_coverage[$file][$line])) {
                $this->_coverage[$file][$line] = $value;
            } else {
                $this->_coverage[$file][$line] += $value;
            }
        }
    }

    protected function _coverage($file, $coverage)
    {
        $result = [];
        $root = $this->parse($file);
        foreach ($root->lines['content'] as $num => $content) {
            $coverable = null;
            foreach ($content['nodes'] as $node) {
                if ($node->coverable && $node->lines['stop'] === $num) {
                    $coverable = $node;
                    break;
                }
            }
            if (!$coverable) {
                continue;
            }
            if (isset($coverage[$num])) {
                $result[$num] = $coverage[$num];
            } else {
                $result[$num] = 0;
            }
        }
        return $result;
    }

    /**
     * Check if a filename is collectable.
     *
     * @param   string  $file A file path.
     * @return  boolean
     */
    public function collectable($file) {
        $file = $this->realpath($file);
        if (preg_match("/eval\(\)'d code$/", $file) || !isset($this->_coverage[$file])) {
            return false;
        }
        return true;
    }

    /**
     * Returns the real path in the original src directory.
     *
     * @param  string $file A file path or cached file path.
     * @return string       The original file path.
     */
    public function realpath($file) {
        $prefix = $this->_prefix;
        return preg_replace("~^{$prefix}~", '', $file);
    }

    /**
     * Return coverage data.
     *
     * @return array The coverage data.
     */
    public function export($file = null)
    {
        if (!$file) {
            $result = [];
            $base = $this->base();
            foreach ($this->_coverage as $file => $coverage) {
                $result[preg_replace("~^{$base}~", '', $file)] = $this->_coverage($file, $coverage);
            }
            return $result;
        }
        return isset($this->_coverage[$file]) ? $this->_coverage($file, $this->_coverage[$file]) : [];
    }


    /**
     * Return the collected metrics from coverage data.
     *
     * @return Metrics The collected metrics.
     */
    public function metrics()
    {
        $this->_metrics = new Metrics();
        foreach ($this->_coverage as $file => $xdebug) {
            $root = $this->parse($file);
            $coverage = $this->export($file);
            $this->_processed = [
                'loc'      => -1,
                'nlloc'    => -1,
                'lloc'     => -1,
                'cloc'     => -1,
                'coverage' => -1
            ];
            $this->_processTree($file, $root->tree, $coverage);
        }
        return $this->_metrics;
    }

    /**
     * Helper for `Collector::metrics()`.
     *
     * @param  string  $file     The processed file.
     * @param  object  $root     The root node of the processed file.
     * @param  object  $nodes    The nodes to collect metrics on.
     * @param  array   $coverage The coverage data.
     * @param  string  $path     The naming of the processed node.
     */
    protected function _processTree($file, $nodes, $coverage, $path = '')
    {
        foreach ($nodes as $node) {
            $this->_processNode($file, $node, $coverage, $path);
        }
    }

    /**
     * Helper for `Collector::metrics()`.
     *
     * @param  string  $file     The processed file.
     * @param  object  $root     The root node of the processed file.
     * @param  object  $node     The node to collect metrics on.
     * @param  array   $coverage The coverage data.
     * @param  string  $path     The naming of the processed node.
     */
    protected function _processNode($file, $node, $coverage, $path)
    {
        if ($node->hasMethods) {
            $path = "{$path}" . $node->name;
            return $this->_processTree($file, $node->tree, $coverage, $path);
        } if ($node->type === 'namespace') {
            $path = "{$path}" . $node->name . '\\';
            return $this->_processTree($file, $node->tree, $coverage, $path);
        }
        $metrics = $this->_processMetrics($file, $node, $coverage);

        if ($node->type === 'function' && !$node->isClosure) {
            $prefix = $node->isMethod ? "{$path}::" : "{$path}\\";
            $path = $prefix . $node->name . '()';
            $metrics['line']['start'] = $node->lines['start'];
            $metrics['line']['stop'] = $node->lines['stop'];
        }
        $this->_metrics->add($path, $metrics);
    }

    /**
     * Helper for `Collector::metrics()`.
     *
     * @param  string  $file     The processed file.
     * @param  object  $node     The node to collect metrics on.
     * @param  array   $coverage The coverage data.
     * @return array             The collected metrics.
     */
    protected function _processMetrics($file, $node, $coverage)
    {
        $metrics = [
            'loc'      => 0,
            'nlloc'    => 0,
            'lloc'     => 0,
            'cloc'     => 0,
            'coverage' => 0
        ];
        if (!$coverage) {
            return $metrics;
        }
        for ($index = $node->lines['start']; $index <= $node->lines['stop']; $index++) {
            $metrics['loc'] = $this->_lineMetric('loc', $index, $metrics['loc']);
            if (!isset($coverage[$index])) {
                $metrics['nlloc'] = $this->_lineMetric('nlloc', $index, $metrics['nlloc']);
                continue;
            }
            $metrics['lloc'] = $this->_lineMetric('lloc', $index, $metrics['lloc']);
            if ($coverage[$index]) {
                $metrics['cloc'] = $this->_lineMetric('cloc', $index, $metrics['cloc']);
                $metrics['coverage'] = $this->_lineMetric('coverage', $index, $metrics['coverage'], $coverage[$index]);
            }
        }
        $metrics['files'][$file] = $file;
        return $this->_methodMetrics($node, $metrics);
    }

    /**
     * Helper for `Collector::metrics()`.
     *
     * @param  string  $type      The metric type.
     * @param  integer $index     The line index.
     * @param  integer $value     The value to update.
     * @param  integer $increment The increment to perform if the line has not already been processed.
     * @return integer            The metric value.
     */
    protected function _lineMetric($type, $index, $value, $increment = 1) {
        if ($this->_processed[$type] >= $index) {
            return $value;
        }
        $this->_processed[$type] = $index;
        $value += $increment;
        return $value;
    }

    /**
     * Helper for `Collector::metrics()`.
     *
     * @param  object  $node    The node to collect metrics on.
     * @param  array   $metrics The metrics of the node.
     * @return array            The updated metrics.
     */
    protected function _methodMetrics($node, $metrics)
    {
        if ($node->type !== 'function' || $node->isClosure) {
            return $metrics;
        }
        $metrics['methods'] = 1;
        if ($metrics['cloc']) {
            $metrics['cmethods'] = 1;
        }
        return $metrics;
    }

    /**
     * Retruns & cache the tree structure of a file.
     *
     * @param string $file the file path to use for building the tree structure.
     */
    public function parse($file)
    {
        if (isset($this->_tree[$file])) {
            return $this->_tree[$file];
        }
        $parser = $this->_classes['parser'];
        return $this->_tree[$file] = $parser::parse(file_get_contents($file), ['lines' => true]);
    }

}
