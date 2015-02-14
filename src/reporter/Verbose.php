<?php
namespace kahlan\reporter;

use set\Set;
use string\String;
use kahlan\analysis\Debugger;

class Verbose extends Terminal
{
    /**
     * Is entering a new spec.
     *
     * @var array
     */
    protected $_new = false;

    /**
     * Current indentation.
     *
     * @var integer
     */
    protected $_indent = 0;

    /**
     * Callback called when entering a new spec.
     *
     * @param object $report The report object.
     */
    public function before($report = null)
    {
        $this->_new = true;
    }

    /**
     * Callback called on successful expect.
     *
     * @param object $report The report object.
     */
    public function pass($report = null)
    {
        if ($this->_new) {
            $this->write("\n");
            $this->write('[Passed] ', 'green');
            $this->write($this->_file($report) . "\n");
            $this->_indent = $this->_messages($report['messages']);
            $this->_new = false;
        }
        $trace = reset($report['backtrace']);
        $line = $trace['line'];
        $this->write(str_repeat('    ', $this->_indent));
        $this->write('expect->');
        $this->write($report['matcher'], 'magenta');
        $this->write('()');
        $this->write(' passed', 'green');
        $this->write(' - ');
        $this->write("line {$line}\n", 'yellow');
    }

    /**
     * Callback called on skipped.
     *
     * @param object $report The report object.
     */
    public function skip($report = null)
    {
        $this->_report($report);
    }

    /**
     * Callback called on failure.
     *
     * @param object $report The report object.
     */
    public function fail($report = null)
    {
        $this->_report($report);
    }

    /**
     * Callback called when an exception occur.
     *
     * @param object $report The report object.
     */
    public function exception($report = null)
    {
        $this->_report($report);
    }

    /**
     * Callback called when a `kahlan\IncompleteException` occur.
     *
     * @param object $report The report object.
     */
    public function incomplete($report = null)
    {
        $this->_report($report);
    }

    /**
     * Callback called at the end of specs processing.
     */
    public function end($results = [])
    {
        $this->write("\n");
        $this->_summary($results);
        $this->_focused($results);
    }
}
