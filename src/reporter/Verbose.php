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
     */
    public function before($report)
    {
        $this->_new = true;
    }

    /**
     * Callback called on successful expect.
     */
    public function pass($report)
    {
        if ($this->_new) {
            $this->write("\n");
            $this->write('[Passed] ', 'green');
            $this->_indent = $this->_messages($report);
            $this->_new = false;
        }
        $trace = reset($report['backtrace']);
        $line = $trace['line'];
        $this->write(str_repeat('    ', $this->_indent));
        $this->write($report['matcher'], 'green');
        $this->write(' expectation');
        $this->write(' passed', 'green');
        $this->write(" (line {$line})");
        $this->write("\n");
    }

    /**
     * Callback called on skipped.
     */
    public function skip($report)
    {
        $this->_report($report);
    }

    /**
     * Callback called on failure.
     */
    public function fail($report)
    {
        $this->_report($report);
    }

    /**
     * Callback called when an exception occur.
     */
    public function exception($report)
    {
        $this->_report($report);
    }

    /**
     * Callback called when a `kahlan\IncompleteException` occur.
     */
    public function incomplete($report)
    {
        $this->_report($report);
    }

    /**
     * Callback called at the end of specs processing.
     */
    public function end($results = [])
    {
        $this->write("\n\n");
        $this->_summary($results);
        $this->_exclusive($results);
    }
}
