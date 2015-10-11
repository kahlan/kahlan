<?php
namespace kahlan\spec\suite\plugin;

use DateTime;
use jit\Interceptor;
use kahlan\plugin\Monkey;
use kahlan\jit\patcher\Monkey as MonkeyPatcher;

use kahlan\spec\fixture\plugin\monkey\Foo;

function mytime() {
    return 245026800;
}

function myrand($min, $max) {
    return 101;
}

class MyDateTime {
    protected $_datetime;

    public function __construct() {
        date_default_timezone_set('UTC');
        $this->_datetime = new DateTime();
        $this->_datetime->setTimestamp(245026800);
    }

    public function __call($name, $params) {
        return call_user_func_array([$this->_datetime, $name], $params);
    }
}

class MyString {

    public static function dump($value) {
        return 'myhashvalue';
    }

}

describe("Monkey", function() {

    /**
     * Save current & reinitialize the Interceptor class.
     */
    before(function() {
        $this->previous = Interceptor::instance();
        Interceptor::unpatch();

        $cachePath = rtrim(sys_get_temp_dir(), DS) . DS . 'kahlan';
        $include = ['kahlan\spec\\'];
        $interceptor = Interceptor::patch(compact('include', 'cachePath'));
        $interceptor->patchers()->add('monkey', new MonkeyPatcher());

    });

    /**
     * Restore Interceptor class.
     */
    after(function() {
        Interceptor::load($this->previous);
    });

    it("patches a core function", function() {

        $foo = new Foo();
        Monkey::patch('time', 'kahlan\spec\suite\plugin\mytime');
        expect($foo->time())->toBe(245026800);

    });

    describe("::patch()", function() {

        it("patches a core function with a closure", function() {

            $foo = new Foo();
            Monkey::patch('time', function(){return 123;});
            expect($foo->time())->toBe(123);

        });

        it("patches a core class", function() {

            $foo = new Foo();
            Monkey::patch('DateTime', 'kahlan\spec\suite\plugin\MyDateTime');
            expect($foo->datetime()->getTimestamp())->toBe(245026800);

        });

        it("patches a function", function() {

            $foo = new Foo();
            Monkey::patch('kahlan\spec\fixture\plugin\monkey\rand', 'kahlan\spec\suite\plugin\myrand');
            expect($foo->rand(0, 100))->toBe(101);

        });

        it("patches a class", function() {

            $foo = new Foo();
            Monkey::patch('kahlan\util\Text', 'kahlan\spec\suite\plugin\MyString');
            expect($foo->dump((object)'hello'))->toBe('myhashvalue');

        });

        it("can unpatch a monkey patch", function() {

            $foo = new Foo();
            Monkey::patch('kahlan\spec\fixture\plugin\monkey\rand', 'kahlan\spec\suite\plugin\myrand');
            expect($foo->rand(0, 100))->toBe(101);

            Monkey::reset('kahlan\spec\fixture\plugin\monkey\rand');
            expect($foo->rand(0, 100))->toBe(50);

        });

    });

});
