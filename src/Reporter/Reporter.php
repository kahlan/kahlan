<?php
namespace Kahlan\Reporter;

class Reporter
{
    /**
     * Starting time.
     *
     * @var float
     */
    protected $_start = 0;

    /**
     * Total of items to reach.
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
     * Current output file
     *
     * @var string
     */
    protected $_outputFile;

    /**
     * The Constructor.
     *
     * @param array $config The config array. Possible values are:
     *                      - `'start' _integer_: A microtime value.
     */
    public function __construct($config = [])
    {
        $defaults = ['start' => microtime(true), 'outputFile' => null];
        $config += $defaults;
        $this->_start = $config['start'];
        $this->_outputFile = $config['outputFile'];
    }

    /**
     * Callback called before any specs processing.
     *
     * @param array $params The suite params array.
     */
    public function start($params)
    {
        $this->_start = $this->_start ?: microtime(true);
        $this->_total = max(1, $params['total']);
    }

    /**
     * Callback called on a suite start.
     *
     * @param object $report The report object of the whole spec.
     */
    public function suiteStart($report = null)
    {
    }

    /**
     * Callback called after a suite execution.
     *
     * @param object $report The report object of the whole spec.
     */
    public function suiteEnd($report = null)
    {
    }

    /**
     * Callback called on a spec start.
     *
     * @param object $report The report object of the whole spec.
     */
    public function specStart($report = null)
    {
    }

    /**
     * Callback called after a spec execution.
     *
     * @param object $report The report object of the whole spec.
     */
    public function specEnd($report = null)
    {
        $this->_current++;
    }

    /**
     * Callback called on successful expect.
     *
     * @param object $report An expect report object.
     */
    public function pass($report = null)
    {
    }

    /**
     * Callback called on failure.
     *
     * @param object $report An expect report object.
     */
    public function fail($report = null)
    {
    }

    /**
     * Callback called when an exception occur.
     *
     * @param object $report An expect report object.
     */
    public function exception($report = null)
    {
    }

    /**
     * Callback called on a skipped spec.
     *
     * @param object $report An expect report object.
     */
    public function skip($report = null)
    {
    }

    /**
     * Callback called when a `Kahlan\IncompleteException` occur.
     *
     * @param object $report An expect report object.
     */
    public function incomplete($report = null)
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
