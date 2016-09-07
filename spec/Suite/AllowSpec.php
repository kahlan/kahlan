<?php
namespace Kahlan\Kahlan\Spec\Suite\Plugin;

use Exception;
use ReflectionMethod;
use InvalidArgumentException;
use DateTime;

use Kahlan\Jit\Interceptor;
use Kahlan\Jit\Patchers;
use Kahlan\Arg;
use Kahlan\Jit\Patcher\Pointcut as PointcutPatcher;
use Kahlan\Jit\Patcher\Monkey as MonkeyPatcher;
use Kahlan\Plugin\Stub;
use Kahlan\IncompleteException;

use Kahlan\Spec\Fixture\Plugin\Monkey\Mon;
use Kahlan\Spec\Fixture\Plugin\Monkey\User;
use Kahlan\Spec\Fixture\Plugin\Pointcut\Foo;
use Kahlan\Spec\Fixture\Plugin\Pointcut\SubBar;

describe("Allow", function() {

    /**
     * Save current & reinitialize the Interceptor class.
     */
    before(function() {
        $this->previous = Interceptor::instance();
        Interceptor::unpatch();

        $cachePath = rtrim(sys_get_temp_dir(), DS) . DS . 'kahlan';
        $include = ['Kahlan\Spec\\'];
        $interceptor = Interceptor::patch(compact('include', 'cachePath'));
        $interceptor->patchers()->add('pointcut', new PointcutPatcher());
        $interceptor->patchers()->add('monkey', new MonkeyPatcher());
    });

    /**
     * Restore Interceptor class.
     */
    after(function() {
        Interceptor::load($this->previous);
    });

    it("monkey patches a class", function() {

        $bar = Stub::create();
        allow($bar)->toReceive('send')->andReturn('EOF');
        allow('Kahlan\Spec\Fixture\Plugin\Pointcut\Bar')->toBe($bar);

        $foo = new Foo();
        expect($foo->bar())->toBe('EOF');

    });

    it("monkey patches a function", function() {

        $mon = new Mon();
        allow('time')->toBe(function() {
            return 123;
        });
        expect($mon->time())->toBe(123);

    });

    it("throws an exception when trying to monkey patch an instance", function() {

        expect(function() {
            $foo = new Foo();
            allow($foo)->toBe(Stub::create());
        })->toThrow(new Exception("Error `toBe()` need to be applied on a fully-namespaced class or function name."));

    });

    it("throws an exception when trying to monkey patch an instance using a generic stub", function() {

        expect(function() {
            $foo = new Foo();
            allow($foo)->toBeOK();
        })->toThrow(new Exception("Error `toBeOK()` need to be applied on a fully-namespaced class or function name."));

    });

    context("with an instance", function() {

        it("stubs a method", function() {

            $foo = new Foo();
            allow($foo)->toReceive('message')->andReturn('Good Bye!');
            expect($foo->message())->toBe('Good Bye!');

        });

        it("stubs only on the stubbed instance", function() {

            $foo = new Foo();
            allow($foo)->toReceive('message')->andReturn('Good Bye!');
            expect($foo->message())->toBe('Good Bye!');

            $foo2 = new Foo();
            expect($foo2->message())->toBe('Hello World!');

        });

        it("stubs a method using a closure", function() {

            $foo = new Foo();
            allow($foo)->toReceive('message')->andReturnUsing(function($param) { return $param; });
            expect($foo->message('Good Bye!'))->toBe('Good Bye!');

        });

        it("stubs a magic method", function() {

            $foo = new Foo();
            allow($foo)->toReceive('magicCall')->andReturn('Magic Call!');
            expect($foo->magicCall())->toBe('Magic Call!');

        });

        it("stubs a magic method using a closure", function() {

            $foo = new Foo();
            allow($foo)->toReceive('magicHello')->andReturnUsing(function($message) { return $message; });
            expect($foo->magicHello('Hello World!'))->toBe('Hello World!');

        });

        it("stubs a static magic method", function() {

            $foo = new Foo();
            allow($foo)->toReceive('::magicCallStatic')->andReturn('Magic Call Static!');
            expect($foo::magicCallStatic())->toBe('Magic Call Static!');

        });

        it("stubs a static magic method using a closure", function() {

            $foo = new Foo();
            allow($foo)->toReceive('::magicHello')->andReturnUsing(function($message) { return $message; });
            expect($foo::magicHello('Hello World!'))->toBe('Hello World!');

        });

        it("overrides previously applied stubs", function() {

            $foo = new Foo();
            allow($foo)->toReceive('magicHello')->andReturn('Hello World!');
            allow($foo)->toReceive('magicHello')->andReturn('Good Bye!');
            expect($foo->magicHello())->toBe('Good Bye!');

        });

        it("throws an exception when trying to call `toReceive()`", function() {

            expect(function() {
                $foo = new Foo();
                allow($foo)->toBeCalled('magicHello')->andReturn('Hello World!');
            })->toThrow(new Exception("Error `toBeCalled()` are are only available on functions not classes/instances."));

        });

        context("with several applied stubs on a same method", function() {

            it("stubs a magic method multiple times", function() {

                $foo = new Foo();
                allow($foo)->toReceive('magic')->with('hello')->andReturn('world');
                allow($foo)->toReceive('magic')->with('world')->andReturn('hello');
                expect($foo->magic('hello'))->toBe('world');
                expect($foo->magic('world'))->toBe('hello');

            });

            it("stubs a static magic method multiple times", function() {

                $foo = new Foo();
                allow($foo)->toReceive('::magic')->with('hello')->andReturn('world');
                allow($foo)->toReceive('::magic')->with('world')->andReturn('hello');
                expect($foo::magic('hello'))->toBe('world');
                expect($foo::magic('world'))->toBe('hello');

            });

        });

        context("using the with() parameter", function() {

            it("stubs on matched parameter", function() {

                $foo = new Foo();
                allow($foo)->toReceive('message')->with('Hello World!')->andReturn('Good Bye!');
                expect($foo->message('Hello World!'))->toBe('Good Bye!');

            });

            it("doesn't stubs on unmatched parameter", function() {

                $foo = new Foo();
                allow($foo)->toReceive('message')->with('Hello World!')->andReturn('Good Bye!');
                expect($foo->message('Hello!'))->not->toBe('Good Bye!');


            });

        });

        context("using the with() parameter and the argument matchers", function() {

            it("stubs on matched parameter", function() {

                $foo = new Foo();
                allow($foo)->toReceive('message')->with(Arg::toBeA('string'))->andReturn('Good Bye!');
                expect($foo->message('Hello World!'))->toBe('Good Bye!');
                expect($foo->message('Hello'))->toBe('Good Bye!');

            });

            it("doesn't stubs on unmatched parameter", function() {

                $foo = new Foo();
                allow($foo)->toReceive('message')->with(Arg::toBeA('string'))->andReturn('Good Bye!');
                expect($foo->message(false))->not->toBe('Good Bye!');
                expect($foo->message(['Hello World!']))->not->toBe('Good Bye!');

            });

        });

        context("with multiple return values", function() {

            it("stubs a method", function() {

                $foo = new Foo();
                allow($foo)->toReceive('message')->andReturn('Good Evening World!', 'Good Bye World!');
                expect($foo->message())->toBe('Good Evening World!');
                expect($foo->message())->toBe('Good Bye World!');
                expect($foo->message())->toBe('Good Bye World!');

            });

        });

        context("with chain of methods", function() {

            it("expects subbed chain to be subbed", function() {

                $foo = new Foo();
                allow($foo)->toReceive('a->b->c')->andReturn('something');
                $query = $foo->a();
                $select = $query->b();
                expect($select->c())->toBe('something');

            });

            it('auto monkey patch core classes using a stub when possible', function() {

                allow('PDO')->toReceive('prepare->fetchAll')->andReturn([['name' => 'bob']]);
                $user = new User();
                expect($user->all())->toBe([['name' => 'bob']]);

            });

            it('allows to stubs a same method twice', function() {

                allow('PDO')->toReceive('prepare->fetchAll')->andReturn([['name' => 'bob']]);
                allow('PDO')->toReceive('prepare->execute')->andReturn(true);
                $user = new User();
                expect($user->all())->toBe([['name' => 'bob']]);
                expect($user->success())->toBe(true);

            });

            it('allows to mix static/dynamic methods', function() {

                allow('Kahlan\Spec\Fixture\Plugin\Monkey\User')->toReceive('::create->all')->andReturn([['name' => 'bob']]);
                $user = User::create();
                expect($user->all())->toBe([['name' => 'bob']]);

            });

            it("throws an exception when trying to stub an instance of a built-in class", function() {

                expect(function() {
                    allow(new DateTime());
                })->toThrow(new InvalidArgumentException("Can't Stub built-in PHP instances, create a test double using `Stub::create()`."));

            });

        });

    });

    context("with an class", function() {

        it("stubs a method", function() {

            allow('Kahlan\Spec\Fixture\Plugin\Pointcut\Foo')
                ->toReceive('message')
                ->andReturn('Good Bye!');

            $foo = new Foo();
            expect($foo->message())->toBe('Good Bye!');
            $foo2 = new Foo();
            expect($foo2->message())->toBe('Good Bye!');

        });

        it("stubs a static method", function() {

            allow('Kahlan\Spec\Fixture\Plugin\Pointcut\Foo')->toReceive('::messageStatic')->andReturn('Good Bye!');
            expect(Foo::messageStatic())->toBe('Good Bye!');

        });

        it("stubs a method using a closure", function() {

            allow('Kahlan\Spec\Fixture\Plugin\Pointcut\Foo')->toReceive('message')->andReturnUsing(function($param) { return $param; });
            $foo = new Foo();
            expect($foo->message('Good Bye!'))->toBe('Good Bye!');

        });

        it("stubs a static method using a closure", function() {

            allow('Kahlan\Spec\Fixture\Plugin\Pointcut\Foo')->toReceive('::messageStatic')->andReturnUsing(function($param) { return $param; });
            expect(Foo::messageStatic('Good Bye!'))->toBe('Good Bye!');

        });

        it("stubs a magic method multiple times", function() {

            allow('Kahlan\Spec\Fixture\Plugin\Pointcut\Foo')->toReceive('::magic')->with('hello')->andReturn('world');
            allow('Kahlan\Spec\Fixture\Plugin\Pointcut\Foo')->toReceive('::magic')->with('world')->andReturn('hello');
            expect(Foo::magic('hello'))->toBe('world');
            expect(Foo::magic('world'))->toBe('hello');

        });

        it("throws an exception when trying to call `toReceive()`", function() {

            expect(function() {
                allow('Kahlan\Spec\Fixture\Plugin\Pointcut\Foo')->toBeCalled('magicHello')->andReturn('Hello World!');
            })->toThrow(new Exception("Error `toBeCalled()` are are only available on functions not classes/instances."));

        });

        context("with multiple return values", function(){

            it("stubs a method", function() {

                allow('Kahlan\Spec\Fixture\Plugin\Pointcut\Foo')
                    ->toReceive('message')
                    ->andReturn('Good Evening World!', 'Good Bye World!');

                $foo = new Foo();
                expect($foo->message())->toBe('Good Evening World!');

                $foo2 = new Foo();
                expect($foo2->message())->toBe('Good Bye World!');

            });

        });

        context("with chain of methods", function() {

            it("expects called chain to be called", function() {

                allow('Kahlan\Spec\Fixture\Plugin\Pointcut\Foo')->toReceive('::getQuery::newQuery::from')->andReturn('something');
                $query = Foo::getQuery();
                $select = $query::newQuery();
                expect($select::from())->toBe('something');

            });

        });

        it('makes built-in PHP class to work', function() {

            allow('PDO')->toBeOK();
            $user = new User();

        });

    });

    context("with a trait", function() {

        it("stubs a method", function() {

            allow('Kahlan\Spec\Fixture\Plugin\Pointcut\SubBar')
                ->toReceive('traitMethod')
                ->andReturn('trait method stubbed !');

            $subBar = new SubBar();
            expect($subBar->traitMethod())->toBe('trait method stubbed !');
            $subBar2 = new SubBar();
            expect($subBar2->traitMethod())->toBe('trait method stubbed !');

        });

    });

    context("with functions", function() {

        it("expects stubbed method to be stubbed as expected", function() {

            $mon = new Mon();
            allow('time')->toBeCalled()->andReturn(123, 456);
            expect($mon->time())->toBe(123);
            expect($mon->time())->toBe(456);

        });

        it("expects stubbed method to be stubbed as expected using a closure", function() {

            $mon = new Mon();
            allow('time')->toBeCalled()->andReturnUsing(function() {return 123;});
            expect($mon->time())->toBe(123);

        });

        it("expects stubbed method to be stubbed only when the with constraint is respected", function() {

            $mon = new Mon();
            allow('Kahlan\Spec\Fixture\Plugin\Monkey\rand')->toBeCalled()->with(10, 20)->andReturn(40);
            expect($mon->rand(0, 10))->toBe(5);
            expect($mon->rand(10, 20))->toBe(40);
        });

        it('makes built-in PHP function to work', function() {

            allow('file_get_contents')->toBeOK();

            $mon = new Mon();
            expect($mon->loadFile())->toBe(null);

        });

        it("throws an exception when trying to call `toReceive()`", function() {

            expect(function() {
                allow('time')->toReceive('something')->andReturn(123, 456);
            })->toThrow(new Exception("Error `toReceive()` are only available on classes/instances not functions."));

        });

    });

    it("throws an exception when trying to call `andReturn()` right away", function() {

        expect(function() {
            allow('time')->andReturn(123);
        })->toThrow(new Exception("You must to call `toReceive()/toBeCalled()` before defining a return value."));

    });

    it("throws an exception when trying to call `andReturn()` right away", function() {

        expect(function() {
            allow('time')->andReturnUsing(function(){});
        })->toThrow(new Exception("You must to call `toReceive()/toBeCalled()` before defining a return value."));

    });

});
