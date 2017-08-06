<?php
namespace Kahlan\Matcher;

class AnyException extends \Exception
{
    /**
     * The exception message.
     *
     * @var string
     */
    protected $message = null;

    /**
     * The exception message.
     *
     * @param string  $message  The exception message.
     * @param integer $code     The exception code.
     * @param string  $previous The previous exception.
     */
    public function __construct($message = null, $code = 0, $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->message = $message;
        $this->code = $code;
    }
}
