<?php
namespace Kahlan\Spec\Fixture\Reporter\Console;

class Exception implements \Iterator
{
    private $position = 0;

    public function __construct($message, $code, $file, $line, $trace)
    {
        $this->position = 0;
        $this->_message = $message;
        $this->_code = $code;
        $this->_file = $file;
        $this->_line = $line;
        $this->_trace = $trace;
    }

    public function getMessage()
    {
        return $this->_message;
    }

    public function getCode()
    {
        return $this->_code;
    }

    public function getFile()
    {
        return $this->_file;
    }

    public function getLine()
    {
        return $this->_line;
    }

    public function getTrace()
    {
        return $this->_trace;
    }

    public function rewind(): void
    {
        $this->position = 0;
    }

    #[\ReturnTypeWillChange]
    public function current()
    {
        return $this->_trace[$this->position];
    }

    #[\ReturnTypeWillChange]
    public function key()
    {
        return $this->position;
    }

    public function next(): void
    {
        ++$this->position;
    }

    public function valid(): bool
    {
        return isset($this->_trace[$this->position]);
    }
}
