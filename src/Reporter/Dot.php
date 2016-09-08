<?php
namespace Kahlan\Reporter;

class Dot extends Terminal
{
    /**
     * Store the current number of dots.
     *é
     * @var integer
     */
    protected $_counter = 0;

    /**
     * Callback called before any specs processing.
     *
     * @param array $args The suite arguments array.
     */
    public function start($args)
    {
        parent::start($args);
        $this->write("\n");
    }

    /**
     * Callback called after a spec execution.
     *
     * @param object $log The log object of the whole spec.
     */
    public function specEnd($log = null)
    {
        switch($log->type()) {
            case 'passed':
                $this->_write('.');
            break;
            case 'skipped':
                $this->_write('S', 'd');
            break;
            case 'pending':
                $this->_write('P', 'cyan');
            break;
            case 'excluded':
                $this->_write('X', 'yellow');
            break;
            case 'failed':
                $this->_write('F', 'red');
            break;
            case 'errored':
                $this->_write('E', 'magenta');
            break;
        }
    }

    /**
     * Callback called at the end of specs processing.
     *
     * @param object $summary The execution summary instance.
     */
    public function end($summary)
    {
        do {
            $this->_write(' ');
        } while ($this->_counter % 80 !== 0);

        $this->write("\n");

        foreach ($summary->logs() as $log) {
            if (!$log->passed()) {
                $this->_report($log);
            }
        }

        $this->write("\n\n");
        $this->_reportSummary($summary);
    }

    /**
     * Outputs the string message in the console.
     *
     * @param string       $string  The string message.
     * @param array|string $options The color options.
     */
    protected function _write($string, $options = null)
    {
        $this->write($string, $options);
        $this->_counter++;
        if ($this->_counter % 80 === 0) {
            $this->write(' ' . floor(($this->_current * 100) / $this->_total) . "%\n");
        }
    }
}
