<?php
namespace Kahlan\Spec\Fixture\Jit\Patcher\Monkey;

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

    public function instantiateWithArguments()
    {
        $this->_db = new PDO(
            "mysql:dbname=testdb;host=localhost",
            'root',
            ''
        );
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

    public function instantiateFromUsedSubnamespace()
    {
        new space\MyClass;
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
        return Text::hash((object) 'hello');
    }

    public function staticCallAndinstantiation() {
        $node = Parser::parse($string);
        return new Parser($node);
    }

    public function staticCallWithComplexArguments()
    {
        return Filters::run($this, 'filterable', func_get_args(), function($next, $message) {
            return "Hello {$message}";
        });
    }

    public function staticCallWithNestedComplexArguments()
    {
        return (Set::extend(parent::_handlers(), [
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
                'datetime' => function($value, $options = []) use ($gmstrtotime) {
                    $options += ['format' => 'Y-m-d H:i:s'];
                    if ($value instanceof DateTime) {
                        $date = $value->format($options['format']);
                    } else {
                        $timestamp = is_numeric($value) ? $value : $gmstrtotime($value);
                        if ($timestamp < 0 || $timestamp === false) {
                            throw new InvalidArgumentException("Invalid date `{$value}`, can't be parsed.");
                        }
                        $date = gmdate($options['format'], $timestamp);
                    }
                    return $this->dialect()->quote((string) $date);
                },
                'boolean' => function($value, $options = []) {
                    return $value ? 'TRUE' : 'FALSE';
                },
                'null'    => function($value, $options = []) {
                    return 'NULL';
                },
                'json'    => function($value, $options = []) {
                    if (is_object($value)) {
                        $value = $value->data();
                    }
                    return $this->dialect()->quote((string) json_encode($value));
                }
            ]
        ]));
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

    public function subChild() {
        if ($options['recursive']) {
            $worker = new RecursiveIteratorIterator($worker, $iteratorFlags);
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

Exemple::reset();
$time = time();
