<?php
namespace Kahlan\Reporter;

class Tap extends Terminal
{
    /**
     * Store the current number of dots.
     *
     * @var integer
     */
    protected $_counter = 0;

    /**
     * Store counters for all types of tests
     *
     * @var array
     */
    protected $_counters = [
        'success' => 0,
        'failed' => 0,
        'skipped' => 0,
        'total' => 0
    ];

    protected $_lines = [];

    /**
     * Callback called before any specs processing.
     *
     * @param array $params The suite params array.
     */
    public function start($params)
    {
        $this->_header = false;
        parent::start($params);
        $this->write("\n");
        $this->write("# Building report it can take some time, please be patient");
    }

    /**
     * Callback called on successful expect.
     *
     * @param object $report An expect report object.
     */
    public function pass($report = null)
    {
        $this->_counters['success'] += 1;
        $this->_counters['total'] += 1;

        $this->_formatTap(true, $report);
    }

    /**
     * Callback called on failure.
     *
     * @param object $report An expect report object.
     */
    public function fail($report = null)
    {
        $this->_counters['failed'] += 1;
        $this->_counters['total'] += 1;

        $this->_formatTap(false, $report);
        $this->_lines[] = "# Actual: {$report->params()["actual"]}";
        $this->_lines[] = "# Expected: {$report->params()["expected"]}";
    }

    /**
     * Callback called when an exception occur.
     *
     * @param object $report An expect report object.
     */
    public function exception($report = null)
    {
        $this->_counters['failed'] += 1;
        $this->_counters['total'] += 1;

        $this->_formatTap(true, $report);
        $exception = $report->exception();
        $this->_lines[] = '# Exception: `' . get_class($exception) .'` Code(' . $exception->getCode() . '):';
        $this->_lines[] = '# Message: ' . $exception->getMessage();
    }

    /**
     * Callback called on a skipped spec.
     *
     * @param object $report An expect report object.
     */
    public function skip($report = null)
    {
        $this->_counters['skipped'] += 1;
        $this->_counters['total'] += 1;

        $this->_formatTap(true, $report);
    }

    /**
     * Callback called when a `Kahlan\IncompleteException` occur.
     *
     * @param object $report An expect report object.
     */
    public function incomplete($report = null)
    {
        $this->_counters['skipped'] += 1;
        $this->_counters['total'] += 1;

        $this->_formatTap(true, $report);
    }

    /**
     * Callback called at the end of specs processing.
     *
     * @param array $results The results array of the execution.
     */
    public function end($results = [])
    {
        $this->write("1..{$this->_counters['total']}\n");
        foreach($this->_lines as $line) {
            $this->write($line . "\n");
        }

        $this->write("# total {$this->_counters['total']}\n");
        $this->write("# pass {$this->_counters['success']}\n");
        $this->write("# fail {$this->_counters['failed']}\n");
        $this->write("# skip {$this->_counters['skipped']}\n");
    }

    /**
     * Export a report to its TAP representation.
     *
     * @param  boolean $success The success value.
     * @param  object  $report  The report to export.
     * @return                  The TAP string representation of the report.
     */
    protected function _formatTap($success, $report)
    {
        $isOk = ($success) ? "ok" : "not ok";
        $message = $report->file() . ": " .trim(implode(" ", $report->messages()));
        $this->_lines[] = "{$isOk} {$this->_counters['total']} {$message}";
    }

}
