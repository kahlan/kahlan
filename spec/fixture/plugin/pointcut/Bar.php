<?php
namespace kahlan\spec\fixture\plugin\pointcut;

class Bar
{
    public function send()
    {
        return 'success';
    }

    public static function sendStatic()
    {
        return 'static success';
    }

    public function overrided()
    {
        return 'Bar';
    }
}
