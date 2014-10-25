<?php
/**
 * Kahlan: PHP Testing Framework
 *
 * @copyright     Copyright 2013, CrysaLEAD
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace kahlan\matcher;

class AnyException extends \Exception
{
    protected $message = null;

    public function __construct($message = null, $code = 0, $previous = null) {
        $this->message = $message;
    }

    public function match($exception) {
        $code = $this->getCode();
        $sameCode = $code ? $code === $exception->getCode() : true;
        $sameMessage = $this->getMessage() === $exception->getMessage();
        $sameMessage = $sameMessage || $this->getMessage() === null;
        return $sameCode && $sameMessage;
    }
}
