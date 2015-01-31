<?php
namespace kahlan\reporter;

class Reporter
{
    /**
     * Starting time
     *
     * @var float
     */
    protected $_start = 0;

    /**
     * Total of items to reach
     *
     * @var integer
     */
    protected $_total = 0;

    /**
     * Current position.
     *
     * @var integer
     */
    protected $_current = 0;

    /**
     * Reporter constructor
     *
     * @param array $config (Unused).
     */
    public function __construct($config = [])
    {
        $defaults = ['start' => microtime(true)];
        $config += $defaults;
        $this->_start = $config['start'];
    }

    /**
     * Callback called before any specs processing.
     *
     * @param array $params The suite params array.
     */
    public function begin($params)
    {
        $this->_start = $this->_start ?: microtime(true);
        $this->_total = max(1, $params['total']);
    }

    /**
     * Callback called before a spec.
     *
     * @param array $report The report array.
     */
    public function before($report = [])
    {
        $this->_current++;
    }

    /**
     * Callback called after a spec.
     *
     * @param array $report The report array.
     */
    public function after($report = [])
    {
    }

    /**
     * Callback called on successful expect.
     *
     * @param array $report The report array.
     */
    public function pass($report = [])
    {
    }

    /**
     * Callback called on failure.
     *
     * @param array $report The report array.
     */
    public function fail($report = [])
    {
    }

    /**
     * Callback called when an exception occur.
     *
     * @param array $report The report array.
     */
    public function exception($report = [])
    {
    }

    /**
     * Callback called on a skipped spec.
     *
     * @param array $report The report array.
     */
    public function skip($report = [])
    {
    }

    /**
     * Callback called when a `kahlan\IncompleteException` occur.
     *
     * @param array $report The report array.
     */
    public function incomplete($report = [])
    {
    }

    /**
     * Callback called at the end of specs processing.
     */
    public function end($results = [])
    {
    }

    /**
     * Callback called at the end of the process.
     */
    public function stop($results = [])
    {
    }
}
