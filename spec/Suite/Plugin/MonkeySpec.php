<?php
namespace Kahlan\Spec\Suite\Plugin;

use DateTime;
use Kahlan\Jit\Interceptor;
use Kahlan\Plugin\Monkey;
use Kahlan\Jit\Patcher\Monkey as MonkeyPatcher;

use Kahlan\Spec\Fixture\Plugin\Monkey\Foo;

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
        $include = ['Kahlan\Spec\\'];
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
        Monkey::patch('time', 'Kahlan\Spec\Suite\Plugin\mytime');
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
            Monkey::patch('DateTime', 'Kahlan\Spec\Suite\Plugin\MyDateTime');
            expect($foo->datetime()->getTimestamp())->toBe(245026800);

        });

        it("patches a function", function() {

            $foo = new Foo();
            Monkey::patch('Kahlan\Spec\Fixture\Plugin\Monkey\rand', 'Kahlan\Spec\Suite\Plugin\myrand');
            expect($foo->rand(0, 100))->toBe(101);

        });

        it("patches a class", function() {

            $foo = new Foo();
            Monkey::patch('Kahlan\Util\Text', 'Kahlan\Spec\Suite\Plugin\MyString');
            expect($foo->dump((object)'hello'))->toBe('myhashvalue');

        });

        it("can unpatch a monkey patch", function() {

            $foo = new Foo();
            Monkey::patch('Kahlan\Spec\Fixture\Plugin\Monkey\rand', 'Kahlan\Spec\Suite\Plugin\myrand');
            expect($foo->rand(0, 100))->toBe(101);

            Monkey::reset('Kahlan\Spec\Fixture\Plugin\Monkey\rand');
            expect($foo->rand(0, 100))->toBe(50);

        });

    });

});
