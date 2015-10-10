<?php
namespace kahlan\reporter;


class Verbose extends Terminal
{
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
     * Callback called on a suite start.
     *
     * @param object $report The report object of the whole spec.
     */
    public function suiteStart($report = null)
    {
        $messages = $report->messages();
        $message = end($messages);
        $this->write("{$message}\n", "b;");
        $this->_indent++;
    }

    /**
     * Callback called after a suite execution.
     *
     * @param object $report The report object of the whole spec.
     */
    public function suiteEnd($report = null)
    {
        $this->_indent--;
    }

    /**
     * Callback called after a spec execution.
     *
     * @param object $report The report object of the whole spec.
     */
    public function specEnd($report = null)
    {
        $this->_reportSpec($report);
    }

    /**
     * Callback called at the end of specs processing.
     */
    public function end($results = [])
    {
        $this->write("\n\n");
        $this->_summary($results);
        $this->_reportFocused($results);
    }
}
