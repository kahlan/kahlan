<?php
namespace Kahlan\Spec\Suite\Matcher;

use Kahlan\Jit\Interceptor;
use Kahlan\Jit\Patcher\Monkey;
use Kahlan\Matcher\ToBeCalled;

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

        context("when using with()", function() {

            it("expects called function called with correct arguments to be called", function() {

                $foo = new Foo();
                expect('Kahlan\Spec\Fixture\Plugin\Monkey\rand')->toBeCalled()->with(5, 10);
                $foo->rand(5, 10);

            });

            it("expects called function called with correct arguments exactly a specified times to be called", function() {

                $foo = new Foo();
                expect('Kahlan\Spec\Fixture\Plugin\Monkey\rand')->toBeCalled()->with(5, 10)->times(2);
                $foo->rand(5, 10);
                $foo->rand(5, 10);

            });

            it("expects called function called with correct arguments not exactly a specified times to be uncalled", function() {

                $foo = new Foo();
                expect('Kahlan\Spec\Fixture\Plugin\Monkey\rand')->not->toBeCalled()->with(5, 10)->times(2);
                $foo->rand(5, 10);
                $foo->rand(10, 10);

            });

        });

        context("when using times()", function() {

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

        });

        context("when using subbing", function() {

            it("expects called method to be called and stubbed as expected", function() {

                $foo = new Foo();
                expect('time')->toBeCalled()->andReturn(123, 456);
                expect($foo->time())->toBe(123);
                expect($foo->time())->toBe(456);

            });

            it("expects called method to be called and stubbed as expected", function() {

                $foo = new Foo();
                expect('time')->toBeCalled()->andRun(function() {
                    return 123;
                });
                expect($foo->time())->toBe(123);

            });

        });

        context("with ordered enabled", function() {

            describe("::match()", function() {

                it("expects uncalled function to be uncalled in a defined order", function() {

                    $foo = new Foo();
                    expect('time')->toBeCalled()->ordered;
                    expect('Kahlan\Spec\Fixture\Plugin\Monkey\rand')->not->toBeCalled()->ordered;
                    $foo->time();

                });

                it("expects called function to be called in a defined order", function() {

                    $foo = new Foo();
                    expect('time')->toBeCalled()->ordered;
                    expect('Kahlan\Spec\Fixture\Plugin\Monkey\rand')->toBeCalled()->ordered;
                    $foo->time();
                    $foo->rand(5, 10);

                });

                it("expects called function called in a different order to be uncalled", function() {

                    $foo = new Foo();
                    expect('time')->toBeCalled()->ordered;
                    expect('Kahlan\Spec\Fixture\Plugin\Monkey\rand')->not->toBeCalled()->ordered;
                    $foo->rand(5, 10);
                    $foo->time();

                });

            });

        });

    });

    describe("->description()", function() {

        it("returns the description message for not received call", function() {

            $foo = new Foo();
            $matcher = new ToBeCalled('time');

            $matcher->resolve([
                'instance' => $matcher,
                'data'     => [
                    'actual' => 'time',
                    'logs'   => []
                ]
            ]);

            $actual = $matcher->description();

            expect($actual['description'])->toBe('be called.');
            expect($actual['data'])->toBe([
                'actual' => 'time()',
                'actual called times' => 0,
                'expected to be called' => 'time()'
            ]);

        });

        it("returns the description message for not received call the specified number of times", function() {

            $foo = new Foo();
            $matcher = new ToBeCalled('time');
            $matcher->times(2);

            $matcher->resolve([
                'instance' => $matcher,
                'data'     => [
                    'actual' => 'time',
                    'logs'   => []
                ]
            ]);

            $actual = $matcher->description();

            expect($actual['description'])->toBe('be called the expected times.');
            expect($actual['data'])->toBe([
                'actual' => 'time()',
                'actual called times' => 0,
                'expected to be called' => 'time()',
                'expected called times' => 2
            ]);

        });

        it("returns the description message for wrong passed arguments", function() {

            $foo = new Foo();
            $matcher = new ToBeCalled('time');
            $matcher->with('Hello World!');

            $foo->time();

            $matcher->resolve([
                'instance' => $matcher,
                'data'     => [
                    'actual' => 'time',
                    'logs'   => []
                ]
            ]);

            $actual = $matcher->description();

            expect($actual['description'])->toBe('be called with expected parameters.');
            expect($actual['data'])->toBe([
                'actual' => 'time()',
                'actual called times' => 1,
                'actual called parameters list' => [
                   []
                ],
                'expected to be called' => 'time()',
                'expected parameters' => [
                    'Hello World!'
                ]
            ]);

        });

    });

});
