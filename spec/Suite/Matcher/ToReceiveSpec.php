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

                it("expects params match the toContain argument matcher", function() {

                    $foo = new Foo();
                    expect($foo)->toReceive('message')->with(Arg::toContain('My Message'));
                    $foo->message(['My Message', 'My Other Message']);

                });

                it("expects params match the argument matchers", function() {

                    $foo = new Foo();
                    expect($foo)->toReceive('message')->with(Arg::toBeA('boolean'));
                    expect($foo)->toReceive('message')->with(Arg::toBeA('string'));
                    $foo->message(true);
                    $foo->message('Hello World');

                });

                it("expects params to not match the toContain argument matcher", function() {

                    $foo = new Foo();
                    expect($foo)->not->toReceive('message')->with(Arg::toContain('Message'));
                    $foo->message(['My Message', 'My Other Message']);

                });

            });

            context("when using times()", function() {

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

            context("when using subbing", function() {

                it("expects called method to be called and stubbed as expected", function() {

                    $foo = new Foo();
                    expect($foo)->toReceive('message')->andReturn('Hello Boy!', 'Hello Man!');
                    expect($foo->message())->toBe('Hello Boy!');
                    expect($foo->message())->toBe('Hello Man!');

                });

                it("expects called method to be called and stubbed as expected", function() {

                    $foo = new Foo();
                    expect($foo)->toReceive('message')->andRun(function() {
                        return 'Hello Girl!';
                    });
                    expect($foo->message())->toBe('Hello Girl!');

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

        context("with ordered enabled", function() {

            describe("::match()", function() {

                it("expects called methods to be called in a defined order", function() {

                    $foo = new Foo();
                    expect($foo)->toReceive('message')->ordered;
                    expect($foo)->toReceive('::version')->ordered;
                    expect($foo)->toReceive('bar')->ordered;
                    $foo->message();
                    $foo::version();
                    $foo->bar();

                });

                it("expects called methods to be called in a defined order only once", function() {

                    $foo = new Foo();
                    expect($foo)->toReceive('message')->ordered->once();
                    expect($foo)->toReceive('::version')->ordered->once();
                    expect($foo)->toReceive('bar')->ordered->once();
                    $foo->message();
                    $foo::version();
                    $foo->bar();

                });

                it("expects called methods to be called in a defined order a specific number of times", function() {

                    $foo = new Foo();
                    expect($foo)->toReceive('message')->ordered->times(1);
                    expect($foo)->toReceive('::version')->ordered->times(2);
                    expect($foo)->toReceive('bar')->ordered->times(3);
                    $foo->message();
                    $foo::version();
                    $foo::version();
                    $foo->bar();
                    $foo->bar();
                    $foo->bar();

                });

                it("expects called methods called in a different order to be uncalled", function() {

                    $foo = new Foo();
                    expect($foo)->toReceive('message')->ordered;
                    expect($foo)->not->toReceive('bar')->ordered;
                    $foo->bar();
                    $foo->message();

                });

                it("expects called methods called a specific number of times but in a different order to be uncalled", function() {

                    $foo = new Foo();
                    expect($foo)->toReceive('message')->ordered->times(1);
                    expect($foo)->toReceive('::version')->ordered->times(2);
                    expect($foo)->not->toReceive('bar')->ordered->times(1);
                    $foo->message();
                    $foo::version();
                    $foo->bar();
                    $foo::version();

                });

                it("expects to work as `toReceive` for the first call", function() {

                    $foo = new Foo();
                    expect($foo)->toReceive('message');
                    $foo->message();

                });

                it("expects called methods are consumated", function() {

                    $foo = new Foo();
                    expect($foo)->toReceive('message')->ordered;
                    expect($foo)->not->toReceive('message')->ordered;
                    $foo->message();

                });

                it("expects called methods are consumated using classname", function() {

                    $foo = new Foo();
                    expect('Kahlan\Spec\Fixture\Plugin\Pointcut\Foo')->toReceive('message')->ordered;
                    expect('Kahlan\Spec\Fixture\Plugin\Pointcut\Foo')->not->toReceive('message')->ordered;
                    $foo->message();

                });

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

            expect($actual['description'])->toBe('receive the expected method.');
            expect($actual['params'])->toBe([
                'actual received calls' => ['__construct'],
                'expected to receive'   => 'method'
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

            expect($actual['description'])->toBe('receive the expected method the expected times.');
            expect($actual['params'])->toBe([
                'actual received calls'   => ['__construct'],
                'expected to receive'     => 'method',
                'expected received times' => 2
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

            expect($actual['description'])->toBe('receive the expected method with expected parameters.');
            expect($actual['params'])->toBe([
                'actual received'                 => 'method',
                'actual received times'           => 1,
                'actual received parameters list' => [['Good Bye!']],
                'expected to receive'             => 'method',
                'expected parameters'             => ['Hello World!']
            ]);

        });

    });

});
