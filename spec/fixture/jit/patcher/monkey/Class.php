<?php
namespace spec\fixture\jit\patcher\monkey;

use kahlan\MongoId;
use kahlan\util\String;

function time() {
    return 0;
}

class Example extends \kahlan\fixture\Parent
{
    use A, B {
        B::smallTalk insteadof A;
        A::bigTalk insteadof B;
    }

    public $type = User::TYPE;

    public function classic()
    {
        rand(2, 5);
    }

    public function rootBased()
    {
        \rand(2, 5);
    }

    public function nested()
    {
        return rand(rand(2, 5), rand(6, 10));
    }

    public function inString()
    {
        'rand(2, 5)';
    }

    public function namespaced()
    {
        time();
    }

    public function rootBasedInsteadOfNamespaced()
    {
        \time();
    }

    public function instantiate()
    {
        new stdClass;
    }

    public function instantiateRootBased()
    {
        new \stdClass;
    }

    public function instantiateFromUsed()
    {
        new MongoId;
    }

    public function instantiateRootBasedFromUsed()
    {
        new \MongoId;
    }

    public function instantiateVariable()
    {
        $class = 'MongoId';
        new $class;
    }

    public function staticCall()
    {
        return Debugger::trace();
    }

    public function staticCallFromUsed()
    {
        return String::hash((object) 'hello');
    }

    public function noIndent()
    {
rand();
    }

    public function closure()
    {
        $func = function() {
            rand(2.5);
        };
        $func();
    }

    public function staticAttribute()
    {
        $type = User::TYPE;
    }

    public function lambda()
    {
        $initializers = [
            'name' => function($self) {
                return basename(str_replace('\\', '/', $self));
            },
            'source' => function($self) {
                return Inflector::tableize($self::meta('name'));
            },
            'title' => function($self) {
                $titleKeys = array('title', 'name');
                $titleKeys = array_merge($titleKeys, (array) $self::meta('key'));
                return $self::hasField($titleKeys);
            }
        ];
    }

    public function ignoreControlStructure()
    {
        array();
        try{} catch (\Exception $e) {};
        compact();
        declare(ticks=1);
        die();
        echo('');
        empty($a);
        eval('');
        exit(-1);
        extract();
        for($i=0;$i<1;$i++) {};
        foreach($array as $key=>$value) {}
        function(){};
        if(true){}
        include('filename');
        include_once('filename');
        if(false){} elseif(true) {}
        isset($a);
        list($a, $b) = ['A', 'B'];
        new self();
        new static();
        parent::hello();
        print('hello');
        require('filename');
        require_once('filename');
        return($a);
        switch($case){
            case (true && true):
                break;
            default:
        }
        unset($a);
        while(false){}
    }
}
