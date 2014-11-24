<?php
namespace kahlan\matcher;

class AnyException extends \Exception
{
    protected $message = null;

    public function __construct($message = null, $code = 0, $previous = null) {
        $this->message = $message;
        $this->code = $code;
    }

}
