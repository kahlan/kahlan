<?php
namespace kahlan\spec\mock\plugin\stub;
use \kahlan\plugin\Stub;

class SuperStubber extends Stub 
{
    public static function generateAbstractMethods($class) {
        return self::_generateAbstractMethods($class);
    }    
}