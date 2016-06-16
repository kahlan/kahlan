<?php
namespace Kahlan\Spec\Suite\Matcher;

use Kahlan\Jit\Interceptor;
use Kahlan\Arg;
use Kahlan\Plugin\Stub;
use Kahlan\Jit\Patcher\Pointcut;
use Kahlan\Matcher\ToReceive;

use Kahlan\Spec\Fixture\Plugin\Pointcut\Foo;
use Kahlan\Spec\Fixture\Plugin\Pointcut\SubBar;

describe("toReceive", function() {

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
            $interceptor->patchers()->add('pointcut', new Pointcut());
        });

        /**
         * Restore Interceptor class.
         */
        after(function() {
            Interceptor::load($this->previous);
        });

        context("with dynamic call", function() {

            it("expects called method to be called", function() {

                $foo = new Foo();
                expect($foo)->toReceive('message');
                $foo->message();

            });

            it("expects called method to be called exactly once", function() {

                $foo = new Foo();
                expect($foo)->toReceive('message')->once();
                $foo->message();

            });

            it("expects called method to be called exactly a specified times", function() {

                $foo = new Foo();
                expect($foo)->toReceive('message')->times(3);
                $foo->message();
                $foo->message();
                $foo->message();

            });

            it("expects called method not called exactly a specified times to be uncalled", function() {

                $foo = new Foo();
                expect($foo)->not->toReceive('message')->times(1);
                $foo->message();
                $foo->message();

            });

            it("expects static method called using non-static way to still called (PHP behavior)", function() {

                $foo = new Foo();
                expect($foo)->toReceive('::version');
                $foo->version();

            });

            it("expects static method called using non-static way to be not called on instance", function() {

                $foo = new Foo();
                expect($foo)->not->toReceive('version');
                $foo->version();

            });

            it("expects uncalled method to be uncalled", function() {

                $foo = new Foo();
                expect($foo)->not->toReceive('message');

            });

            context("when using with()", function() {

                it("expects called method to be called with correct params", function() {

                    $foo = new Foo();
                    expect($foo)->toReceive('message')->with('My Message', 'My Other Message');
                    $foo->message('My Message', 'My Other Message');

                });

                it("expects called method with incorrect params to not be called", function() {

                    $foo = new Foo();
                    expect($foo)->not->toReceive('message')->with('My Message');
                    $foo->message('Incorrect Message');

                });

                it("expects called method with missing params to not be called", function() {

                    $foo = new Foo();
                    expect($foo)->not->toReceive('message')->with('My Message');
                    $foo->message();

                });

            });

            context("when using with() and matchers", function() {

                it("expects params match the toContain argument matcher", function() {

                    $foo = new Foo();
                    expect($foo)->toReceive('message')->with(Arg::toContain('My Message'));
                    $foo->message(['My Message', 'My Other Message']);

                });

                it("expects params match the argument matchers", function() {

                    $foo = new Foo();
                    expect($foo)->toReceive('message')->with(Arg::toBeA('boolean'));
                    expect($foo)->toReceiveNext('message')->with(Arg::toBeA('string'));
                    $foo->message(true);
                    $foo->message('Hello World');

                });

                it("expects params to not match the toContain argument matcher", function() {

                    $foo = new Foo();
                    expect($foo)->not->toReceive('message')->with(Arg::toContain('Message'));
                    $foo->message(['My Message', 'My Other Message']);

                });

            });

            context("when using classname", function() {

                it("expects called method to be called", function() {

                    $foo = new Foo();
                    expect('Kahlan\Spec\Fixture\Plugin\Pointcut\Foo')->toReceive('message');
                    $foo->message();

                });

                it("expects called method to be called exactly once", function() {

                    $foo = new Foo();
                    expect('Kahlan\Spec\Fixture\Plugin\Pointcut\Foo')->toReceive('message')->once();
                    $foo->message();

                });

                it("expects called method to be called exactly a specified times", function() {

                    $foo = new Foo();
                    expect('Kahlan\Spec\Fixture\Plugin\Pointcut\Foo')->toReceive('message')->times(3);
                    $foo->message();
                    $foo->message();
                    $foo->message();

                });

                it("expects called method not called exactly a specified times to be uncalled", function() {

                    $foo = new Foo();
                    expect('Kahlan\Spec\Fixture\Plugin\Pointcut\Foo')->not->toReceive('message')->times(1);
                    $foo->message();
                    $foo->message();

                });

                it("expects uncalled method to be uncalled", function() {

                    $foo = new Foo();
                    expect('Kahlan\Spec\Fixture\Plugin\Pointcut\Foo')->not->toReceive('message');

                });

                it("expects called method to be uncalled using a wrong classname", function() {

                    $foo = new Foo();
                    expect('Kahlan\Spec\Fixture\Plugin\Pointcut\FooFoo')->not->toReceive('message');
                    $foo->message();

                });

                it("expects not overrided method to also be called on method's __CLASS__", function() {

                    $bar = new SubBar();
                    expect('Kahlan\Spec\Fixture\Plugin\Pointcut\Bar')->toReceive('send');
                    expect('Kahlan\Spec\Fixture\Plugin\Pointcut\SubBar')->toReceive('send');
                    $bar->send();

                });

                it("expects overrided method to not be called on method's __CLASS__", function() {

                    $bar = new SubBar();
                    expect('Kahlan\Spec\Fixture\Plugin\Pointcut\Bar')->not->toReceive('overrided');
                    expect('Kahlan\Spec\Fixture\Plugin\Pointcut\SubBar')->toReceive('overrided');
                    $bar->overrided();

                });

            });
        });

        context("with static call", function() {

            it("expects called method to be called", function() {

                expect('Kahlan\Spec\Fixture\Plugin\Pointcut\Foo')->toReceive('::version');
                Foo::version();

            });

            it("expects called method to be called exactly once", function() {

                expect('Kahlan\Spec\Fixture\Plugin\Pointcut\Foo')->toReceive('::version')->once();
                Foo::version();

            });

            it("expects called method to be called exactly a specified times", function() {

                expect('Kahlan\Spec\Fixture\Plugin\Pointcut\Foo')->toReceive('::version')->times(3);
                Foo::version();
                Foo::version();
                Foo::version();

            });

            it("expects called method not called exactly a specified times to be uncalled", function() {

                expect('Kahlan\Spec\Fixture\Plugin\Pointcut\Foo')->not->toReceive('::version')->times(1);
                Foo::version();
                Foo::version();

            });

            it("expects called method to not be dynamically called", function() {

                expect('Kahlan\Spec\Fixture\Plugin\Pointcut\Foo')->not->toReceive('version');
                Foo::version();

            });

            it("expects called method on instance to be called on classname", function() {

                $foo = new Foo();
                expect('Kahlan\Spec\Fixture\Plugin\Pointcut\Foo')->toReceive('::version');
                $foo::version();

            });

            it("expects called method on instance to not be dynamically called", function() {

                $foo = new Foo();
                expect('Kahlan\Spec\Fixture\Plugin\Pointcut\Foo')->not->toReceive('version');
                $foo::version();

            });

            it("expects called method on instance to be called on classname (alternative syntax)", function() {

                $foo = new Foo();
                expect($foo)->toReceive('::version');
                $foo::version();

            });

        });

    });

    describe("->description()", function() {

        it("returns the description message", function() {

            $stub = Stub::create();
            $matcher = new ToReceive($stub, 'method');
            $message = $matcher->message();

            expect($message)->toBeAnInstanceOf('Kahlan\Plugin\Call\Message');

        });

        it("returns the description message for not received call", function() {

            $stub = Stub::create();
            $matcher = new ToReceive($stub, 'method');

            $matcher->resolve([
                'instance' => $matcher,
                'params'   => [
                    'actual'   => $stub,
                    'expected' => 'method',
                    'logs'     => []
                ]
            ]);

            $actual = $matcher->description();

            expect($actual['description'])->toBe('receive the correct message.');
            expect($actual['params'])->toBe([
                'actual received' => ['__construct'],
                'expected'        => 'method'
            ]);

        });

        it("returns the description message for not received call the specified number of times", function() {

            $stub = Stub::create();
            $matcher = new ToReceive($stub, 'method');
            $matcher->times(2);

            $matcher->resolve([
                'instance' => $matcher,
                'params'   => [
                    'actual'   => $stub,
                    'expected' => 'method',
                    'logs'     => []
                ]
            ]);

            $actual = $matcher->description();

            expect($actual['description'])->toBe('receive the correct message.');
            expect($actual['params'])->toBe([
                'actual received' => ['__construct'],
                'expected'        => 'method',
                'called times'    => 2
            ]);

        });

        it("returns the description message for wrong passed arguments", function() {

            $stub = Stub::create();
            $matcher = new ToReceive($stub, 'method');
            $matcher->with('Hello World!');

            $stub->method('Good Bye!');

            $matcher->resolve([
                'instance' => $matcher,
                'params'   => [
                    'actual'   => $stub,
                    'expected' => 'method',
                    'logs'     => []
                ]
            ]);

            $actual = $matcher->description();

            expect($actual['description'])->toBe('receive correct parameters.');
            expect($actual['params'])->toBe([
                'actual with' => ['Good Bye!'],
                'expected with' => ['Hello World!']
            ]);

        });

    });

});
