<?php
namespace kahlan\spec\fixture\jit\patcher\monkey;

use kahlan\MongoId;
use kahlan\util\Text;
use sub\name\space;

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
    {$__KMONKEY__0 = \kahlan\plugin\Monkey::patched(__NAMESPACE__ , 'rand', true);
        $__KMONKEY__0(2, 5);
    }

    public function rootBased()
    {$__KMONKEY__1 = \kahlan\plugin\Monkey::patched(null , 'rand');
        $__KMONKEY__1(2, 5);
    }

    public function nested()
    {$__KMONKEY__2 = \kahlan\plugin\Monkey::patched(__NAMESPACE__ , 'rand', true);
        return $__KMONKEY__2($__KMONKEY__2(2, 5), $__KMONKEY__2(6, 10));
    }

    public function inString()
    {
        'rand(2, 5)';
    }

    public function namespaced()
    {$__KMONKEY__3 = \kahlan\plugin\Monkey::patched(__NAMESPACE__ , 'time', true);
        $__KMONKEY__3();
    }

    public function rootBasedInsteadOfNamespaced()
    {$__KMONKEY__4 = \kahlan\plugin\Monkey::patched(null , 'time');
        $__KMONKEY__4();
    }

    public function instantiate()
    {$__KMONKEY__5 = \kahlan\plugin\Monkey::patched(__NAMESPACE__ , 'stdClass', false);
        new $__KMONKEY__5;
    }

    public function instantiateRootBased()
    {$__KMONKEY__6 = \kahlan\plugin\Monkey::patched(null , 'stdClass');
        new $__KMONKEY__6;
    }

    public function instantiateFromUsed()
    {$__KMONKEY__7 = \kahlan\plugin\Monkey::patched(null, 'kahlan\MongoId');
        new $__KMONKEY__7;
    }

    public function instantiateRootBasedFromUsed()
    {$__KMONKEY__8 = \kahlan\plugin\Monkey::patched(null , 'MongoId');
        new $__KMONKEY__8;
    }

    public function instantiateFromUsedSubnamespace()
    {$__KMONKEY__9 = \kahlan\plugin\Monkey::patched(null, 'sub\name\space\MyClass');
        new $__KMONKEY__9;
    }

    public function instantiateVariable()
    {
        $class = 'MongoId';
        new $class;
    }

    public function staticCall()
    {$__KMONKEY__10 = \kahlan\plugin\Monkey::patched(__NAMESPACE__ , 'Debugger', false);
        return $__KMONKEY__10::trace();
    }

    public function staticCallFromUsed()
    {$__KMONKEY__11 = \kahlan\plugin\Monkey::patched(null, 'kahlan\util\Text');
        return $__KMONKEY__11::hash((object) 'hello');
    }

    public function noIndent()
    {$__KMONKEY__12 = \kahlan\plugin\Monkey::patched(__NAMESPACE__ , 'rand', true);
$__KMONKEY__12();
    }

    public function closure()
    {
        $func = function() {$__KMONKEY__13 = \kahlan\plugin\Monkey::patched(__NAMESPACE__ , 'rand', true);
            $__KMONKEY__13(2.5);
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
            'name' => function($self) {$__KMONKEY__14 = \kahlan\plugin\Monkey::patched(__NAMESPACE__ , 'basename', true);$__KMONKEY__15 = \kahlan\plugin\Monkey::patched(__NAMESPACE__ , 'str_replace', true);
                return $__KMONKEY__14($__KMONKEY__15('\\', '/', $self));
            },
            'source' => function($self) {$__KMONKEY__16 = \kahlan\plugin\Monkey::patched(__NAMESPACE__ , 'Inflector', false);
                return $__KMONKEY__16::tableize($self::meta('name'));
            },
            'title' => function($self) {$__KMONKEY__17 = \kahlan\plugin\Monkey::patched(__NAMESPACE__ , 'array_merge', true);
                $titleKeys = array('title', 'name');
                $titleKeys = $__KMONKEY__17($titleKeys, (array) $self::meta('key'));
                return $self::hasField($titleKeys);
            }
        ];
    }

    public function subChild() {$__KMONKEY__18 = \kahlan\plugin\Monkey::patched(__NAMESPACE__ , 'RecursiveIteratorIterator', false);
        if ($options['recursive']) {
            $worker = new $__KMONKEY__18($worker, $iteratorFlags);
        }
    }

    public function ignoreControlStructure()
    {
        array();
        true and(true);
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
        true or(true);
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
        while(false){};
        true xor(true);
    }

    public function ignoreControlStructureInUpperCase()
    {
        ARRAY();
        TRUE AND(TRUE);
        TRY{} CATCH (\EXCEPTION $E) {};
        COMPACT();
        DECLARE(TICKS=1);
        DIE();
        ECHO('');
        EMPTY($A);
        EVAL('');
        EXIT(-1);
        EXTRACT();
        FOR($I=0;$I<1;$I++) {};
        FOREACH($ARRAY AS $KEY=>$VALUE) {}
        FUNCTION(){};
        IF(TRUE){}
        INCLUDE('FILENAME');
        INCLUDE_ONCE('FILENAME');
        IF(FALSE){} ELSEIF(TRUE) {}
        ISSET($A);
        LIST($A, $B) = ['A', 'B'];
        TRUE OR(TRUE);
        NEW SELF();
        NEW STATIC();
        PARENT::HELLO();
        PRINT('HELLO');
        REQUIRE('FILENAME');
        REQUIRE_ONCE('FILENAME');
        RETURN($A);
        SWITCH($CASE){
            CASE (TRUE && TRUE):
                BREAK;
            DEFAULT:
        }
        UNSET($A);
        WHILE(FALSE){};
        TRUE XOR(TRUE);
    }
}
