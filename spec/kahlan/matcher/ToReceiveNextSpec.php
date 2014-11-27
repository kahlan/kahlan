<?php
namespace spec\kahlan\matcher;

use jit\Interceptor;
use jit\Parser;
use kahlan\jit\patcher\Pointcut;
use kahlan\matcher\ToReceiveNext;

use spec\fixture\plugin\pointcut\Foo;

describe("toReceiveNext", function() {

    describe("::match()", function() {

        /**
         * Save current & reinitialize the Interceptor class.
         */
        before(function() {
            $this->previous = Interceptor::instance();
            Interceptor::unpatch();

            $cachePath = rtrim(sys_get_temp_dir(), DS) . DS . 'kahlan';
            $exclude = ['kahlan\\'];
            $interceptor = Interceptor::patch(compact('exclude', 'cachePath'));
            $interceptor->patchers()->add('pointcut', new Pointcut());
        });

        /**
         * Restore Interceptor class.
         */
        after(function() {
            Interceptor::load($this->previous);
        });

        it("expects called methods to be called in a defined order", function() {

            $foo = new Foo();
            expect($foo)->toReceive('message');
            expect($foo)->toReceiveNext('::version');
            expect($foo)->toReceiveNext('bar');
            $foo->message();
            $foo::version();
            $foo->bar();

        });

        it("expects called methods to not be called in a different order", function() {

            $foo = new Foo();
            expect($foo)->toReceive('message');
            expect($foo)->not->toReceiveNext('bar');
            $foo->bar();
            $foo->message();

        });

        it("expects to work as `toReceive` for the first call", function() {

            $foo = new Foo();
            expect($foo)->toReceiveNext('message');
            $foo->message();

        });

        it("expects called methods are consumated", function() {

            $foo = new Foo();
            expect($foo)->toReceive('message');
            expect($foo)->not->toReceiveNext('message');
            $foo->message();

        });

        it("expects called methods are consumated using classname", function() {

            $foo = new Foo();
            expect('spec\fixture\plugin\pointcut\Foo')->toReceive('message');
            expect('spec\fixture\plugin\pointcut\Foo')->not->toReceiveNext('message');
            $foo->message();

        });

    });

});
