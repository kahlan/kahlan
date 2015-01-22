<?php
namespace kahlan\spec\fixture\plugin\pointcut;

class Foo
{
    protected $_classes = [
        'bar' => 'kahlan\spec\fixture\plugin\pointcut\Bar'
    ];

    protected $_inited = false;

    protected $_status = 'none';

    protected $_message = 'Hello World!';

    protected static $_messageStatic = 'Hello Static World!';

    public function __construct()
    {
        $this->_inited = true;
    }

    public function inited()
    {
        return $this->_inited;
    }

    public function message($message = null)
    {
        if ($message === null) {
            return $this->_message;
        }
        $this->_message = $message;
    }

    public static function messageStatic($message = null)
    {
        if ($message === null) {
            return static::$_messageStatic;
        }
        static::$_messageStatic = $message;
    }

    public function bar()
    {
        $bar = $this->_classes['bar'];
        $bar = new $bar();
        return $bar->send();
    }

    public function __call($name, $params)
    {
    }

    public static function __callStatic($name, $params)
    {
    }

    public static function version()
    {
        return '0.0.8b';
    }
}
