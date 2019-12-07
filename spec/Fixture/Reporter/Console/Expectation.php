<?php
namespace Kahlan\Spec\Fixture\Reporter\Console;

class Expectation
{
    public function __construct($type, $data, $matcherName, $file, $line, $not, $description)
    {
        $this->_type = $type;
        $this->_data = $data;
        $this->_matcherName = $matcherName;
        $this->_file = $file;
        $this->_line = $line;
        $this->_not = $not;
        $this->_description = $description;
    }

    public function type()
    {
        return $this->_type;
    }

    public function data()
    {
        return $this->_data;
    }

    public function matcherName()
    {
        return $this->_matcherName;
    }

    public function file()
    {
        return $this->_file;
    }

    public function line()
    {
        return $this->_line;
    }

    public function not()
    {
        return $this->_not;
    }

    public function description()
    {
        return $this->_description;
    }
}
