<?php
namespace Kahlan\Spec\Fixture\Reporter\Console;

class Suite
{
    protected $_messages = [];

    public function __construct(array $messages)
    {
        $this->_messages = $messages;
    }

    public function messages()
    {
        return $this->_messages;
    }
}
