<?php
namespace Kahlan\Spec\Suite\Matcher;

use Kahlan\Jit\Interceptor;
use Kahlan\Arg;
use Kahlan\Plugin\Stub;
use Kahlan\Jit\Patcher\Monkey;
use Kahlan\Matcher\ToReceive;

use Kahlan\Spec\Fixture\Plugin\Monkey\Foo;

describe("toBeCalled", function() {

    describe("::match()", function() {

        /**
         * Save current & reinitialize the Interceptor class.
         */
        before(function() {
            $this->previous = Interceptor::instance();
            Interceptor::unpatch();

            $cachePath = rtrim(sys_get_temp_dir(), DS) . DS . 'kahlan';
            $include = ['Kahlan\Spec\\'];
            $interceptor = Interceptor::patch(compact('include', 'cachePath'));
            $interceptor->patchers()->add('monkey', new Monkey());
        });

        /**
         * Restore Interceptor class.
         */
        after(function() {
            Interceptor::load($this->previous);
        });

        it("expects uncalled function to be uncalled", function() {

            $foo = new Foo();
            expect('time')->not->toBeCalled();

        });

        it("expects called function to be called", function() {

            $foo = new Foo();
            expect('time')->toBeCalled();
            $foo->time();

        });

        it("expects called function to be called exactly a specified times", function() {

            $foo = new Foo();
            expect('time')->toBeCalled()->times(3);
            $foo->time();
            $foo->time();
            $foo->time();

        });

        it("expects called function not called exactly a specified times to be uncalled", function() {

            $foo = new Foo();
            expect('time')->not->toBeCalled()->times(1);
            $foo->time();
            $foo->time();

        });

        it("expects called function called with correct params to be called", function() {

            $foo = new Foo();
            expect('Kahlan\Spec\Fixture\Plugin\Monkey\rand')->toBeCalled()->with(5, 10);
            $foo->rand(5, 10);

        });

        it("expects called function called with correct params exactly a specified times to be called", function() {

            $foo = new Foo();
            expect('Kahlan\Spec\Fixture\Plugin\Monkey\rand')->toBeCalled()->with(5, 10)->times(2);
            $foo->rand(5, 10);
            $foo->rand(5, 10);

        });

        it("expects called function called with correct params not exactly a specified times to be uncalled", function() {

            $foo = new Foo();
            expect('Kahlan\Spec\Fixture\Plugin\Monkey\rand')->not->toBeCalled()->with(5, 10)->times(2);
            $foo->rand(5, 10);
            $foo->rand(10, 10);

        });

    });

});
