<?php
namespace kahlan;

class PhpErrorException extends \Exception
{
    protected $message;

    protected $code;

    protected $file;

    protected $line;

    protected $trace;

    public function __construct ($config = [], $code = 0)
    {
        if (is_string($config)) {
            $this->message = $config;
            $this->code = $code;
        }
        if (!is_array($config)) {
            return;
        }
        $defaults = [
            'message' => '',
            'code'    => 0,
            'file'    => '',
            'line'    => 0,
            'trace'   => []
        ];
        $config += $defaults;

        foreach ($config as $key => $value) {
            $this->{$key} = $value;
        }
    }
}