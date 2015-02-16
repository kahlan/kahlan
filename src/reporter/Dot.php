<?php
namespace kahlan\reporter;

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
     * @param array $params The suite params array.
     */
    public function start($params)
    {
        parent::start($params);
        $this->write("\n");
    }

    /**
     * Callback called on successful expect.
     *
     * @param object $report An expect report object.
     */
    public function pass($report = null)
    {
        $this->_write('.');
    }

    /**
     * Callback called on failure.
     *
     * @param object $report An expect report object.
     */
    public function fail($report = null)
    {
        $this->_write('F', 'red');
    }

    /**
     * Callback called when an exception occur.
     *
     * @param object $report An expect report object.
     */
    public function exception($report = null)
    {
        $this->_write('E', 'magenta');
    }

    /**
     * Callback called on a skipped spec.
     *
     * @param object $report An expect report object.
     */
    public function skip($report = null)
    {
        $this->_write('S', 'cyan');
    }

    /**
     * Callback called when a `kahlan\IncompleteException` occur.
     *
     * @param object $report An expect report object.
     */
    public function incomplete($report = null)
    {
        $this->_write('I', 'yellow');
    }

    /**
     * Callback called at the end of specs processing.
     */
    public function end($results = [])
    {
        do {
            $this->_write(' ');
        } while ($this->_counter % 80 !== 0);

        $this->write("\n");

        foreach ($results['specs'] as $type => $reports) {
            foreach ($reports as $report) {
                if ($report->type() !== 'pass' && $report->type() !== 'skip') {
                    $this->_report($report);
                }
            }
        }

        $this->write("\n\n");
        $this->_summary($results);
        $this->_reportFocused($results);
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
