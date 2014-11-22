<?php
namespace kahlan\reporter;

use kahlan\reporter\coverage\Collector;

class Coverage extends Terminal
{
    /**
     * Class dependencies.
     *
     * @var array
     */
    protected static $_classes = [
        'interceptor' => 'kahlan\jit\Interceptor'
    ];

    /**
     * Collect time
     *
     * @var float
     */
    protected $_time = 0;

    /**
     * The coverage verbosity
     *
     * @param integer
     */
    protected $_verbosity = 0;

    /**
     * Reference to the coverage collector driver.
     *
     * @param object
     */
    protected $_collector = '';

    /**
     * Status of the reporter.
     *
     * @var array
     */
    protected $_enabled = true;

    /**
     * Display coverage results in the console.
     *
     * @param array $options The options for the reporter, the options are:
     *                       - `'verbosity`' _integer|string_: The verbosity level:
     *                         - 1      : overall coverage value for the whole code.
     *                         - 2      : overall coverage by namespaces.
     *                         - 3      : overall coverage by classes.
     *                         - 4      : overall coverage by methods and functions.
     *                         - string : coverage for a fully namespaced (class/method/namespace) string.
     */

    public function __construct($options = [])
    {
        parent::__construct($options);
        $defaults = ['verbosity' => 1];
        $options += $defaults;
        $verbosity = $options['verbosity'];
        $this->_verbosity = is_numeric($verbosity) ? (integer) $verbosity : (string) $verbosity;

        if (is_string($this->_verbosity)) {
            $class = preg_replace('/(::)?\w+\(\)$/', '', $this->_verbosity);
            $interceptor = static::$_classes['interceptor'];
            $loader = $interceptor::instance();

            if ($loader && $path = $loader->findPath($class)) {
                $options['path'] = $path;
            }
        }

        $this->_collector = new Collector($options);
    }

    /**
     * Callback called before any specs processing.
     *
     * @param array $params The suite params array.
     */
    public function begin($params)
    {
    }

    /**
     * Callback called before a spec.
     */
    public function before()
    {
        if (!$this->enabled()) {
            return;
        }
        $this->_collector->start();
    }

    /**
     * Callback called after a spec.
     */
    public function after()
    {
        if (!$this->enabled()) {
            return;
        }
        $this->_collector->stop();
    }

    /**
     * Returns the collector.
     *
     * @return object
     */
    public function collector()
    {
        return $this->_collector;
    }

    /**
     * Return the base path used to compute relative paths.
     *
     * @return string
     */
    public function base() {
        return $this->_collector->base();
    }

    /**
     * Returns the coverage result.
     *
     * @return array
     */
    public function export()
    {
        return $this->_collector->export();
    }

    /**
     * Returns the metrics about the coverage result.
     */
    public function metrics()
    {
        $this->_start = microtime(true);
        $result = $this->_collector->metrics();
        $this->_time = microtime(true) - $this->_start;
        return $result;
    }

    /**
     * Output some metrics info.
     *
     * @param Metrics $metrics A metrics instance.
     * @param array   $options The options for the reporter, the options are:
     *                         - `'verbosity`' _integer|string_: The verbosity level:
     *                           - 1      : overall coverage value for the whole code.
     *                           - 2      : overall coverage by namespaces.
     *                           - 3      : overall coverage by classes.
     *                           - 4      : overall coverage by methods and functions.
     *                           - string : coverage for a fully namespaced (class/method/namespace) string.
     */
    protected function _renderMetrics($metrics, $verbosity = 1)
    {
        $type = $metrics->type();
        if ($verbosity === 2 && ($type === 'class' || $type === 'function')) {
            return;
        }
        if ($verbosity === 3 && ($type === 'function' || $type === 'method')) {
            return;
        }
        $name = $metrics->name();
        $stats = $metrics->data();
        $percent = number_format($stats['percent'], 2);
        $style = $this->_style($percent);
        $this->write(str_pad("Lines: {$percent}%", 15), $style);
        $this->write(trim(str_pad("({$stats['cloc']}/{$stats['lloc']})", 20) . "{$name}"));
        $this->write("\n");
        if ($verbosity === 1) {
            return;
        }
        foreach ($metrics->childs() as $child) {
            $this->_renderMetrics($child, $verbosity);
        }
    }

    /**
     * Output the coverage report of a metrics instance.
     *
     * @param Metrics $metrics A metrics instance.
     */
    protected function _renderCoverage($metrics)
    {
        $stats = $metrics->data();
        foreach ($stats['files'] as $file) {
            $this->write("File: {$file}" . "\n\n");

            $lines = file($file);

            $coverage = $this->_collector->export($file);

            if (isset($stats['line'])) {
                $start = $stats['line']['start'];
                $stop = $stats['line']['stop'];
            } else {
                $start = 0;
                $stop = count($lines) - 1;
            }

            for ($i = $start; $i <= $stop; $i++) {
                $value = isset($coverage[$i]) ? $coverage[$i] : null;
                $line = str_pad($i + 1, 6, ' ', STR_PAD_LEFT);
                $line .= ':' . str_pad($value, 6, ' ');
                $line .= $lines[$i];
                if ($value) {
                    $this->write($line, 'n;green');
                } elseif ($value === 0) {
                    $this->write($line, 'n;red');
                } else {
                    $this->write($line);
                }
            }
            $this->write("\n\n");
        }
    }

    /**
     * Helper determinig a color from a coverage rate.
     *
     * @param integer $percent The coverage rate in percent.
     */
    protected function _style($percent)
    {
        switch(true) {
            case $percent >= 80:
                return 'n;green';
            break;
            case $percent >= 60:
                return 'n;default';
            break;
            case $percent >= 40:
                return 'n;yellow';
            break;
        }
        return 'n;red';
    }

    /**
     * Callback called at the end of the process.
     */
    public function stop($results = [])
    {
        $this->write("\nCoverage Summary\n----------------\n\n");
        if (is_numeric($this->_verbosity)) {
            $this->_renderMetrics($this->metrics(), $this->_verbosity);
        } else {
            $metrics = $this->metrics()->get($this->_verbosity);
            $this->_renderMetrics($metrics);
            $this->write("\n");
            $this->_renderCoverage($metrics);
        }
        $time = number_format($this->_time, 3);
        $this->write("\nCollected in {$time} seconds\n\n\n");
    }

    /**
     * Return the status of the reporter.
     *
     * @return boolean $active
     */
    public function enabled()
    {
        return $this->_enabled;
    }

    /**
     * Enable this reporter.
     */
    public function enable()
    {
        $this->_enabled = true;
        $this->_collector->start();
    }

    /**
     * Disable this reporter.
     */
    public function disable()
    {
        $this->_enabled = false;
        $this->_collector->stop();
    }
}
