<?php
namespace Kahlan\Spec\Fixture\Reporter\Console;

class Summary
{
    protected $_logs = [];

    public function __construct($logs)
    {
        $this->_logs = $logs;
    }

    public function logs($type = null)
    {
        if ($type === null) {
            return $this->_logs;
        }

        return $this->_filterLogsByType($type);
    }

    public function passed()
    {
        return $this->_countLogsByType('passed');
    }

    public function skipped()
    {
        return $this->_countLogsByType('skipped');
    }

    public function pending()
    {
        return $this->_countLogsByType('pending');
    }

    public function excluded()
    {
        return $this->_countLogsByType('excluded');
    }

    public function failed()
    {
        return $this->_countLogsByType('failed');
    }

    public function errored()
    {
        return $this->_countLogsByType('errored');
    }

    public function expectation()
    {
        return count($this->_logs) - $this->skipped() - $this->pending() - $this->excluded();
    }

    public function executable()
    {
        return $this->passed() + $this->failed() + $this->errored();
    }

    public function memoryUsage()
    {
        return 2000000;
    }

    public function get($type)
    {
        return [];
    }

    protected function _countLogsByType($type)
    {
        return count($this->_filterLogsByType($type));
    }

    protected function _filterLogsByType($type)
    {
        return array_filter($this->_logs, function ($log) use ($type) {
            return $log->type() === $type;
        });
    }
}
