<?php
namespace spec\plugin;

use kahlan\Arg;
use kahlan\jit\Interceptor;
use kahlan\jit\Patchers;
use kahlan\jit\patcher\Pointcut;
use kahlan\analysis\Parser;
use kahlan\plugin\Stub;

use spec\fixture\plugin\pointcut\Foo;
use spec\fixture\plugin\pointcut\SubBar;
use spec\fixture\plugin\stub\Doz;

describe("Stub", function() {

    /**
     * Save current & reinitialize the Interceptor class.
     */
    before(function() {
        $this->previous = Interceptor::loader();
        Interceptor::unpatch();

        $patchers = new Patchers();
        $patchers->add('pointcut', new Pointcut());
        Interceptor::patch(compact('patchers'));
    });

    /**
     * Restore Interceptor class.
     */
    after(function() {
        Interceptor::loader($this->previous);
    });

    describe("::on()", function() {

        context("with an instance", function() {

            it("stubs a method", function() {

                $foo = new Foo();
                Stub::on($foo)->method('message')->andReturn('Good Bye!');
                expect($foo->message())->toBe('Good Bye!');

            });

            it("stubs only on the stubbed instance", function() {

                $foo = new Foo();
                Stub::on($foo)->method('message')->andReturn('Good Bye!');
                expect($foo->message())->toBe('Good Bye!');

                $foo2 = new Foo();
                expect($foo2->message())->toBe('Hello World!');

            });

            it("stubs a method using a closure", function() {

                $foo = new Foo();
                Stub::on($foo)->method('message', function($param) { return $param; });
                expect($foo->message('Good Bye!'))->toBe('Good Bye!');

            });

            it("stubs a magic method", function() {

                $foo = new Foo();
                Stub::on($foo)->method('magicCall')->andReturn('Magic Call!');
                expect($foo->magicCall())->toBe('Magic Call!');

            });

            it("stubs a magic method using a closure", function() {

                $foo = new Foo();
                Stub::on($foo)->method('magicHello', function($message) { return $message; });
                expect($foo->magicHello('Hello World!'))->toBe('Hello World!');

            });

            it("stubs a static magic method", function() {

                $foo = new Foo();
                Stub::on($foo)->method('::magicCallStatic')->andReturn('Magic Call Static!');
                expect($foo::magicCallStatic())->toBe('Magic Call Static!');

            });

            it("stubs a static magic method using a closure", function() {

                $foo = new Foo();
                Stub::on($foo)->method('::magicHello', function($message) { return $message; });
                expect($foo::magicHello('Hello World!'))->toBe('Hello World!');

            });

            context("using the with() parameter", function() {

                it("stubs on matched parameter", function() {

                    $foo = new Foo();
                    Stub::on($foo)->method('message')->with('Hello World!')->andReturn('Good Bye!');
                    expect($foo->message('Hello World!'))->toBe('Good Bye!');

                });

                it("doesn't stubs on unmatched parameter", function() {

                    $foo = new Foo();
                    Stub::on($foo)->method('message')->with('Hello World!')->andReturn('Good Bye!');
                    expect($foo->message('Hello!'))->not->toBe('Good Bye!');


                });

            });

            context("using the with() parameter and the argument matchers", function() {

                it("stubs on matched parameter", function() {

                    $foo = new Foo();
                    Stub::on($foo)->method('message')->with(Arg::toBeA('string'))->andReturn('Good Bye!');
                    expect($foo->message('Hello World!'))->toBe('Good Bye!');
                    expect($foo->message('Hello'))->toBe('Good Bye!');

                });

                it("doesn't stubs on unmatched parameter", function() {

                    $foo = new Foo();
                    Stub::on($foo)->method('message')->with(Arg::toBeA('string'))->andReturn('Good Bye!');
                    expect($foo->message(false))->not->toBe('Good Bye!');
                    expect($foo->message(['Hello World!']))->not->toBe('Good Bye!');

                });

            });

            context("with multiple return values", function(){

                it("stubs a method", function() {

                    $foo = new Foo();
                    Stub::on($foo)->method('message')->andReturn('Good Evening World!', 'Good Bye World!');
                    expect($foo->message())->toBe('Good Evening World!');
                    expect($foo->message())->toBe('Good Bye World!');
                    expect($foo->message())->toBe('Good Bye World!');

                });

                it("stubs methods with an array", function() {

                    $foo = new Foo();
                    Stub::on($foo)->method([
                        'message' => ['Good Evening World!', 'Good Bye World!'],
                        'bar' => ['Hello Bar!']
                    ]);
                    expect($foo->message())->toBe('Good Evening World!');
                    expect($foo->message())->toBe('Good Bye World!');
                    expect($foo->bar())->toBe('Hello Bar!');

                });

            });

        });

        context("with an class", function() {

            it("stubs a method", function() {

                Stub::on('spec\fixture\plugin\pointcut\Foo')
                    ->method('message')
                    ->andReturn('Good Bye!');

                $foo = new Foo();
                expect($foo->message())->toBe('Good Bye!');
                $foo2 = new Foo();
                expect($foo2->message())->toBe('Good Bye!');

            });

            it("stubs a static method", function() {

                Stub::on('spec\fixture\plugin\pointcut\Foo')->method('::messageStatic')->andReturn('Good Bye!');
                expect(Foo::messageStatic())->toBe('Good Bye!');

            });

            it("stubs a method using a closure", function() {

                Stub::on('spec\fixture\plugin\pointcut\Foo')->method('message', function($param) { return $param; });
                $foo = new Foo();
                expect($foo->message('Good Bye!'))->toBe('Good Bye!');

            });

            it("stubs a static method using a closure", function() {

                Stub::on('spec\fixture\plugin\pointcut\Foo')->method('::messageStatic', function($param) { return $param; });
                expect(Foo::messageStatic('Good Bye!'))->toBe('Good Bye!');

            });

            context("with multiple return values", function(){

                it("stubs a method", function() {

                    Stub::on('spec\fixture\plugin\pointcut\Foo')
                        ->method('message')
                        ->andReturn('Good Evening World!', 'Good Bye World!');

                    $foo = new Foo();
                    expect($foo->message())->toBe('Good Evening World!');

                    $foo2 = new Foo();
                    expect($foo2->message())->toBe('Good Bye World!');

                });

                it("stubs methods with an array", function() {

                    Stub::on('spec\fixture\plugin\pointcut\Foo')->method([
                        'message' => ['Good Evening World!', 'Good Bye World!'],
                        'bar' => ['Hello Bar!']
                    ]);

                    $foo = new Foo();
                    expect($foo->message())->toBe('Good Evening World!');

                    $foo2 = new Foo();
                    expect($foo2->message())->toBe('Good Bye World!');

                    $foo3 = new Foo();
                    expect($foo3->bar())->toBe('Hello Bar!');

                });

            });

        });

        context("with a trait", function() {

            it("stubs a method", function() {

                Stub::on('spec\fixture\plugin\pointcut\SubBar')
                    ->method('traitMethod')
                    ->andReturn('trait method stubbed !');

                $subBar = new SubBar();
                expect($subBar->traitMethod())->toBe('trait method stubbed !');
                $subBar2 = new SubBar();
                expect($subBar2->traitMethod())->toBe('trait method stubbed !');

            });

        });

    });

    describe("::create()", function() {

        it("stubs an instance", function() {

            $stub = Stub::create();
            expect(is_object($stub))->toBe(true);
            expect(get_class($stub))->toMatch("/^spec\\\plugin\\\stub\\\Stub\d+$/");

        });

        it("names a stub instance", function() {

            $stub = Stub::create(['class' => 'spec\stub\MyStub']);
            expect(is_object($stub))->toBe(true);
            expect(get_class($stub))->toBe('spec\stub\MyStub');

        });

        it("stubs an instance with a parent class", function() {

            $stub = Stub::create(['extends' => 'string\String']);
            expect(is_object($stub))->toBe(true);
            expect(get_parent_class($stub))->toBe('string\String');

        });

        it("stubs an instance using a trait", function() {

            $stub = Stub::create(['uses' => 'spec\mock\plugin\stub\HelloTrait']);
            expect($stub->hello())->toBe('Hello World From Trait!');

        });

        it("stubs an instance implementing some interface", function() {

            $stub = Stub::create(['implements' => ['ArrayAccess', 'Iterator']]);
            $interfaces = class_implements($stub);
            expect($interfaces)->toHaveLength(3);
            expect(isset($interfaces['ArrayAccess']))->toBe(true);
            expect(isset($interfaces['Iterator']))->toBe(true);
            expect(isset($interfaces['Traversable']))->toBe(true);

        });

        it("stubs an instance with multiple stubbed methods", function() {

            $stub = Stub::create();
            Stub::on($stub)->method([
                'message' => ['Good Evening World!', 'Good Bye World!'],
                'bar' => ['Hello Bar!']
            ]);

            expect($stub->message())->toBe('Good Evening World!');
            expect($stub->message())->toBe('Good Bye World!');
            expect($stub->bar())->toBe('Hello Bar!');

        });

        it("stubs static methods on a stub instance", function() {

            $stub = Stub::create();
            Stub::on($stub)->method([
                '::magicCallStatic' => ['Good Evening World!', 'Good Bye World!']
            ]);

            expect($stub::magicCallStatic())->toBe('Good Evening World!');
            expect($stub::magicCallStatic())->toBe('Good Bye World!');

        });

        it("produces unique instance", function() {

            $stub = Stub::create();
            $stub2 = Stub::create();

            expect(get_class($stub))->not->toBe(get_class($stub2));

        });

        it("stubs instances with some magic methods if no parent defined", function() {

            $stub = Stub::create();

            expect($stub)->toReceive('__get');
            expect($stub)->toReceiveNext('__set');
            expect($stub)->toReceiveNext('__isset');
            expect($stub)->toReceiveNext('__unset');
            expect($stub)->toReceiveNext('__sleep');
            expect($stub)->toReceiveNext('__toString');
            expect($stub)->toReceiveNext('__invoke');
            expect(get_class($stub))->toReceive('__wakeup');
            expect(get_class($stub))->toReceiveNext('__clone');

            $prop = $stub->prop;
            $stub->prop = $prop;
            isset($stub->prop);
            unset($stub->prop);
            $serialized = serialize($stub);
            unserialize($serialized);
            $string = (string) $stub;
            $stub();
            $stub2 = clone $stub;

        });

        it("stubs instances with a custom method", function() {

            $stub = Stub::create([
                'methods' => ['method1']
            ]);

            expect(method_exists($stub, 'method1'))->toBe(true);
            expect(method_exists($stub, 'method2'))->toBe(false);

        });

        it("stubs instances with a custom method which returns a reference", function() {

            $stub = Stub::create([
                'methods' => ['&method1']
            ]);

            $stub->method1();
            expect(method_exists($stub, 'method1'))->toBe(true);

            $array = [];
            Stub::on($stub)->method('method1', function() use (&$array) {
                $array[] = 'in';
            });

            $result = $stub->method1();
            $result[] = 'out';
            expect($array)->toBe(['in'/*, 'out'*/]); //I guess that's the limitation of the system.

        });

    });

    describe("::classname()", function() {

        it("stubs class", function() {

            $stub = Stub::classname();
            expect($stub)->toMatch("/^spec\\\plugin\\\stub\\\Stub\d+$/");

        });

        it("names a stub class", function() {

            $stub = Stub::classname(['class' => 'spec\stub\MyStaticStub']);
            expect(is_string($stub))->toBe(true);
            expect($stub)->toBe('spec\stub\MyStaticStub');

        });

        it("stubs a stub class with multiple methods", function() {

            $classname = Stub::classname();
            Stub::on($classname)->method([
                'message' => ['Good Evening World!', 'Good Bye World!'],
                'bar' => ['Hello Bar!']
            ]);

            $stub = new $classname();
            expect($stub->message())->toBe('Good Evening World!');

            $stub2 = new $classname();
            expect($stub->message())->toBe('Good Bye World!');

            $stub3 = new $classname();
            expect($stub->bar())->toBe('Hello Bar!');

        });

        it("stubs static methods on a stub class", function() {

            $classname = Stub::classname();
            Stub::on($classname)->method([
                '::magicCallStatic' => ['Good Evening World!', 'Good Bye World!']
            ]);

            expect($classname::magicCallStatic())->toBe('Good Evening World!');
            expect($classname::magicCallStatic())->toBe('Good Bye World!');

        });

        it("produces unique classname", function() {

            $stub = Stub::classname();
            $stub2 = Stub::classname();

            expect($stub)->not->toBe($stub2);

        });

        it("stubs classes with `construct()` if no parent defined", function() {

            $class = Stub::classname();
            expect($class)->toReceive('__construct');
            $stub = new $class();

        });

    });

   describe("::generate()", function() {

        it("overrides the construct method", function() {

            $result = Stub::generate([
                'class' => 'spec\plugin\stub\Stub',
                'methods' => ['__construct'],
                'magicMethods' => false
            ]);

            $expected = <<<EOD
<?php

namespace spec\\plugin\\stub;

class Stub {

    public function __construct() {}

}
?>
EOD;
            expect($result)->toBe($expected);

        });

        it("generates interface methods", function() {

            $result = Stub::generate([
                'class'        => 'spec\plugin\stub\Stub',
                'implements'   => ['Countable'],
                'magicMethods' => false
            ]);

            $expected = <<<EOD
<?php

namespace spec\\plugin\\stub;

class Stub implements \\Countable {

    function count() {}

}
?>
EOD;
            expect($result)->toBe($expected);

        });

        it("generates use statement", function() {

            $result = Stub::generate([
                'class'      => 'spec\plugin\stub\Stub',
                'uses'       => ['spec\mock\plugin\stub\HelloTrait'],
                'magicMethods' => false
            ]);

            $expected = <<<EOD
<?php

namespace spec\\plugin\\stub;

class Stub {

    use \\spec\\mock\\plugin\\stub\\HelloTrait;

}
?>
EOD;
            expect($result)->toBe($expected);

        });

        it("generates abstract parent class methods", function() {

            $result = Stub::generate([
                'class'      => 'spec\plugin\stub\Stub',
                'extends'    => 'spec\fixture\plugin\stub\Doz'
            ]);

            $expected = <<<EOD
<?php

namespace spec\\plugin\\stub;

class Stub extends \\spec\\fixture\\plugin\\stub\\Doz {

    function bar(\$var1 = NULL, array \$var2 = array()) {}

}
?>
EOD;
            expect($result)->toBe($expected);

        });

    });

});
