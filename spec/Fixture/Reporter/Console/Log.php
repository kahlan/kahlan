<?php
namespace Kahlan\Spec\Fixture\Reporter\Console;

class Log
{
    protected $_type = '';
    protected $_messages = [];
    protected $_file = '';
    protected $_line = 0;
    protected $_expectations = [];
    protected $_exception = null;

    public function __construct($type, array $messages, $file = '', $line = 0, $expectations = [], $exception = null)
    {
        $this->_type = $type;
        $this->_messages = $messages;
        $this->_file = $file;
        $this->_line = $line;
        $this->_expectations = $expectations;
        $this->_exception = $exception;
    }

    public function type()
    {
        return $this->_type;
    }

    public function messages()
    {
        return $this->_messages;
    }

    public function passed()
    {
        return $this->_type !== 'failed' && $this->_type !== 'errored';
    }

    public function file()
    {
        return $this->_file;
    }

    public function line()
    {
        return $this->_line;
    }

    public function children()
    {
        return $this->_expectations;
    }

    public function exception()
    {
        return $this->_exception;
    }
}