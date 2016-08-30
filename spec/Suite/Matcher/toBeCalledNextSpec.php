<?php
namespace Kahlan\Spec\Suite\Matcher;

use Kahlan\Jit\Interceptor;
use Kahlan\Arg;
use Kahlan\Plugin\Stub;
use Kahlan\Jit\Patcher\Monkey;
use Kahlan\Matcher\ToReceive;

use Kahlan\Spec\Fixture\Plugin\Monkey\Foo;

describe("toBeCalledNext", function() {

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

        it("expects uncalled function to be uncalled in a defined order", function() {

            $foo = new Foo();
            expect('time')->toBeCalled();
            expect('Kahlan\Spec\Fixture\Plugin\Monkey\rand')->not->toBeCalledNext();
            $foo->time();

        });

        it("expects called function to be called in a defined order", function() {

            $foo = new Foo();
            expect('time')->toBeCalled();
            expect('Kahlan\Spec\Fixture\Plugin\Monkey\rand')->toBeCalledNext();
            $foo->time();
            $foo->rand(5, 10);

        });

        it("expects called function called in a different order to be uncalled", function() {

            $foo = new Foo();
            expect('time')->toBeCalled();
            expect('Kahlan\Spec\Fixture\Plugin\Monkey\rand')->not->toBeCalledNext();
            $foo->rand(5, 10);
            $foo->time();

        });

    });

});
