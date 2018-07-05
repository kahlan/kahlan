<?php
namespace Kahlan\Spec\Fixture\Jit\Patcher\Monkey;$__KMONKEY__28=\Kahlan\Plugin\Monkey::patched(__NAMESPACE__, 'Exemple', null, $__KMONKEY__28__);$__KMONKEY__29=\Kahlan\Plugin\Monkey::patched(__NAMESPACE__ , null, 'time', $__KMONKEY__29__);

use Kahlan\MongoId;
use Kahlan\Util\Text;
use sub\name\space;

function time() {
    return 0;
}

class Example extends \Kahlan\Fixture\Parent
{
    use A, B {
        B::smallTalk insteadof A;
        A::bigTalk insteadof B;
    }

    public $type = User::TYPE;

    public function classic()
    {$__KMONKEY__0=\Kahlan\Plugin\Monkey::patched(__NAMESPACE__ , null, 'rand', $__KMONKEY__0__);
        $__KMONKEY__0(2, 5);
    }

    public function rootBased()
    {$__KMONKEY__1=\Kahlan\Plugin\Monkey::patched(null , null, 'rand', $__KMONKEY__1__);
        $__KMONKEY__1(2, 5);
    }

    public function nested()
    {$__KMONKEY__2=\Kahlan\Plugin\Monkey::patched(__NAMESPACE__ , null, 'rand', $__KMONKEY__2__);
        return $__KMONKEY__2($__KMONKEY__2(2, 5), $__KMONKEY__2(6, 10));
    }

    public function inString()
    {
        'rand(2, 5)';
    }

    public function namespaced()
    {$__KMONKEY__3=\Kahlan\Plugin\Monkey::patched(__NAMESPACE__ , null, 'time', $__KMONKEY__3__);
        $__KMONKEY__3();
    }

    public function rootBasedInsteadOfNamespaced()
    {$__KMONKEY__4=\Kahlan\Plugin\Monkey::patched(null , null, 'time', $__KMONKEY__4__);
        $__KMONKEY__4();
    }

    public function instantiate()
    {$__KMONKEY__5=\Kahlan\Plugin\Monkey::patched(__NAMESPACE__, 'stdClass', null, $__KMONKEY__5__);
        new $__KMONKEY__5;
    }

    public function instantiateWithArguments()
    {$__KMONKEY__6=\Kahlan\Plugin\Monkey::patched(__NAMESPACE__, 'PDO', null, $__KMONKEY__6__);
        $this->_db = new $__KMONKEY__6(
            "mysql:dbname=testdb;host=localhost",
            'root',
            ''
        );
    }

    public function instantiateRootBased()
    {$__KMONKEY__7=\Kahlan\Plugin\Monkey::patched(null, 'stdClass', null, $__KMONKEY__7__);
        new $__KMONKEY__7;
    }

    public function instantiateFromUsed()
    {$__KMONKEY__8=\Kahlan\Plugin\Monkey::patched(null, 'Kahlan\MongoId', null, $__KMONKEY__8__);
        new $__KMONKEY__8;
    }

    public function instantiateRootBasedFromUsed()
    {$__KMONKEY__9=\Kahlan\Plugin\Monkey::patched(null, 'MongoId', null, $__KMONKEY__9__);
        new $__KMONKEY__9;
    }

    public function instantiateFromUsedSubnamespace()
    {$__KMONKEY__10=\Kahlan\Plugin\Monkey::patched(null, 'sub\name\space\MyClass', null, $__KMONKEY__10__);
        new $__KMONKEY__10;
    }

    public function instantiateVariable()
    {
        $class = 'MongoId';
        new $class;
    }

    public function staticCall()
    {$__KMONKEY__11=\Kahlan\Plugin\Monkey::patched(__NAMESPACE__, 'Debugger', null, $__KMONKEY__11__);
        return $__KMONKEY__11::trace();
    }

    public function staticCallFromUsed()
    {$__KMONKEY__12=\Kahlan\Plugin\Monkey::patched(null, 'Kahlan\Util\Text', null, $__KMONKEY__12__);
        return $__KMONKEY__12::hash((object) 'hello');
    }

    public function staticCallAndinstantiation() {$__KMONKEY__13=\Kahlan\Plugin\Monkey::patched(__NAMESPACE__, 'Parser', null, $__KMONKEY__13__);
        $node = $__KMONKEY__13::parse($string);
        return new $__KMONKEY__13($node);
    }

    public function staticCallWithComplexArguments()
    {$__KMONKEY__14=\Kahlan\Plugin\Monkey::patched(__NAMESPACE__, 'Filters', null, $__KMONKEY__14__);
        return $__KMONKEY__14::run($this, 'filterable', func_get_args(), function($next, $message) {
            return "Hello {$message}";
        });
    }

    public function staticCallWithNestedComplexArguments()
    {$__KMONKEY__15=\Kahlan\Plugin\Monkey::patched(__NAMESPACE__, 'Set', null, $__KMONKEY__15__);
        return ($__KMONKEY__15::extend(parent::_handlers(), [
            'datasource' => [
                'decimal' => function($value, $options = []) {
                    $options += ['precision' => 2, 'decimal' => '.', 'separator' => ''];
                    return $number_format($value, $options['precision'], $options['decimal'], $options['separator']);
                },
                'quote' => function($value, $options = []) {
                    return $this->dialect()->quote((string) $value);
                },
                'date' => function($value, $options = []) {
                    return $this->convert('datasource', 'datetime', $value, ['format' => 'Y-m-d']);
                },
                'datetime' => function($value, $options = []) use ($gmstrtotime) {$__KMONKEY__16=\Kahlan\Plugin\Monkey::patched(__NAMESPACE__ , null, 'is_numeric', $__KMONKEY__16__);$__KMONKEY__17=\Kahlan\Plugin\Monkey::patched(__NAMESPACE__, 'InvalidArgumentException', null, $__KMONKEY__17__);$__KMONKEY__18=\Kahlan\Plugin\Monkey::patched(__NAMESPACE__ , null, 'gmdate', $__KMONKEY__18__);
                    $options += ['format' => 'Y-m-d H:i:s'];
                    if ($value instanceof DateTime) {
                        $date = $value->format($options['format']);
                    } else {
                        $timestamp = $__KMONKEY__16($value) ? $value : $gmstrtotime($value);
                        if ($timestamp < 0 || $timestamp === false) {
                            throw new $__KMONKEY__17("Invalid date `{$value}`, can't be parsed.");
                        }
                        $date = $__KMONKEY__18($options['format'], $timestamp);
                    }
                    return $this->dialect()->quote((string) $date);
                },
                'boolean' => function($value, $options = []) {
                    return $value ? 'TRUE' : 'FALSE';
                },
                'null'    => function($value, $options = []) {
                    return 'NULL';
                },
                'json'    => function($value, $options = []) {$__KMONKEY__19=\Kahlan\Plugin\Monkey::patched(__NAMESPACE__ , null, 'is_object', $__KMONKEY__19__);$__KMONKEY__20=\Kahlan\Plugin\Monkey::patched(__NAMESPACE__ , null, 'json_encode', $__KMONKEY__20__);
                    if ($__KMONKEY__19($value)) {
                        $value = $value->data();
                    }
                    return $this->dialect()->quote((string) $__KMONKEY__20($value));
                }
            ]
        ]));
    }

    public function noIndent()
    {$__KMONKEY__21=\Kahlan\Plugin\Monkey::patched(__NAMESPACE__ , null, 'rand', $__KMONKEY__21__);
$__KMONKEY__21();
    }

    public function closure()
    {
        $func = function() {$__KMONKEY__22=\Kahlan\Plugin\Monkey::patched(__NAMESPACE__ , null, 'rand', $__KMONKEY__22__);
            $__KMONKEY__22(2.5);
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
            'name' => function($self) {$__KMONKEY__23=\Kahlan\Plugin\Monkey::patched(__NAMESPACE__ , null, 'str_replace', $__KMONKEY__23__);$__KMONKEY__24=\Kahlan\Plugin\Monkey::patched(__NAMESPACE__ , null, 'basename', $__KMONKEY__24__);
                return $__KMONKEY__24($__KMONKEY__23('\\', '/', $self));
            },
            'source' => function($self) {$__KMONKEY__25=\Kahlan\Plugin\Monkey::patched(__NAMESPACE__, 'Inflector', null, $__KMONKEY__25__);
                return $__KMONKEY__25::tableize($self::meta('name'));
            },
            'title' => function($self) {$__KMONKEY__26=\Kahlan\Plugin\Monkey::patched(__NAMESPACE__ , null, 'array_merge', $__KMONKEY__26__);
                $titleKeys = array('title', 'name');
                $titleKeys = $__KMONKEY__26($titleKeys, (array) $self::meta('key'));
                return $self::hasField($titleKeys);
            }
        ];
    }

    public function subChild() {$__KMONKEY__27=\Kahlan\Plugin\Monkey::patched(__NAMESPACE__, 'RecursiveIteratorIterator', null, $__KMONKEY__27__);
        if ($options['recursive']) {
            $worker = new $__KMONKEY__27($worker, $iteratorFlags);
        }
    }

    public function ignoreControlStructure()
    {
        array();
        true and(true);
        try{} catch (\Exception $e) {};
        clone();
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
        func_get_arg();
        func_get_args();
        func_num_args();
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
        throw($e);
        unset($a);
        while(false){};
        true xor(true);
        yield (int) $value;
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
        YIELD (int) $value;
    }

    public function ignoreBackslashedControlStructure()
    {
        \compact();
        \extract();
        \func_get_arg();
        \func_get_args();
        \func_num_args();
    }
}

$__KMONKEY__28::reset();
$time = $__KMONKEY__29();
