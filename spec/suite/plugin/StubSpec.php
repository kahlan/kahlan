<?php
namespace kahlan\kahlan\spec\suite\plugin;

use ReflectionMethod;
use InvalidArgumentException;

use jit\Interceptor;
use jit\Patchers;
use kahlan\Arg;
use kahlan\jit\patcher\Pointcut;
use kahlan\plugin\Stub;
use kahlan\IncompleteException;

use kahlan\spec\fixture\plugin\pointcut\Foo;
use kahlan\spec\fixture\plugin\pointcut\SubBar;

describe("Stub", function() {

    /**
     * Save current & reinitialize the Interceptor class.
     */
    before(function() {
        $this->previous = Interceptor::instance();
        Interceptor::unpatch();

        $cachePath = rtrim(sys_get_temp_dir(), DS) . DS . 'kahlan';
        $include = ['kahlan\spec\\'];
        $interceptor = Interceptor::patch(compact('include', 'cachePath'));
        $interceptor->patchers()->add('pointcut', new Pointcut());
    });

    /**
     * Restore Interceptor class.
     */
    after(function() {
        Interceptor::load($this->previous);
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

            it("throw when stub a method using closure and using andReturn()", function() {

                expect(function() {
                    $foo = new Foo();
                    Stub::on($foo)->method('message', function($param) { return $param; })->andReturn(true);
                })->toThrow(new \Exception("Closure already set."));

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

            it("overrides previously applied stubs", function() {

                $foo = new Foo();
                Stub::on($foo)->method('magicHello')->andReturn('Hello World!');
                Stub::on($foo)->method('magicHello')->andReturn('Good Bye!');
                expect($foo->magicHello())->toBe('Good Bye!');

            });

            context("with several applied stubs on a same method", function() {

                it("stubs a magic method multiple times", function() {

                    $foo = new Foo();
                    Stub::on($foo)->method('magic')->with('hello')->andReturn('world');
                    Stub::on($foo)->method('magic')->with('world')->andReturn('hello');
                    expect($foo->magic('hello'))->toBe('world');
                    expect($foo->magic('world'))->toBe('hello');

                });

                it("stubs a static magic method multiple times", function() {

                    $foo = new Foo();
                    Stub::on($foo)->method('::magic')->with('hello')->andReturn('world');
                    Stub::on($foo)->method('::magic')->with('world')->andReturn('hello');
                    expect($foo::magic('hello'))->toBe('world');
                    expect($foo::magic('world'))->toBe('hello');

                });

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

            context("with multiple return values", function() {

                it("stubs a method", function() {

                    $foo = new Foo();
                    Stub::on($foo)->method('message')->andReturn('Good Evening World!', 'Good Bye World!');
                    expect($foo->message())->toBe('Good Evening World!');
                    expect($foo->message())->toBe('Good Bye World!');
                    expect($foo->message())->toBe('Good Bye World!');

                });

            });


            context("with ->methods()", function() {

                it("stubs methods using return values as an array", function() {

                    $foo = new Foo();
                    Stub::on($foo)->methods([
                        'message' => ['Good Evening World!', 'Good Bye World!'],
                        'bar' => ['Hello Bar!']
                    ]);
                    expect($foo->message())->toBe('Good Evening World!');
                    expect($foo->message())->toBe('Good Bye World!');
                    expect($foo->bar())->toBe('Hello Bar!');

                });

                it("stubs methods using closure", function() {

                    $foo = new Foo();
                    Stub::on($foo)->methods([
                        'message' => function() {
                            return 'Good Evening World!';
                        },
                        'bar' => function() {
                            return 'Hello Bar!';
                        }
                    ]);
                    expect($foo->message())->toBe('Good Evening World!');
                    expect($foo->bar())->toBe('Hello Bar!');

                });

                it("throw an exception with invalid definition", function() {

                    $closure = function() {
                        $foo = new Foo();
                        Stub::on($foo)->methods([
                            'bar' => 'Hello Bar!'
                        ]);
                    };
                    $message = "Stubbed method definition for `bar` must be a closure or an array of returned value(s).";
                    expect($closure)->toThrow(new InvalidArgumentException($message));

                });

            });

        });

        context("with an class", function() {

            it("stubs a method", function() {

                Stub::on('kahlan\spec\fixture\plugin\pointcut\Foo')
                    ->method('message')
                    ->andReturn('Good Bye!');

                $foo = new Foo();
                expect($foo->message())->toBe('Good Bye!');
                $foo2 = new Foo();
                expect($foo2->message())->toBe('Good Bye!');

            });

            it("stubs a static method", function() {

                Stub::on('kahlan\spec\fixture\plugin\pointcut\Foo')->method('::messageStatic')->andReturn('Good Bye!');
                expect(Foo::messageStatic())->toBe('Good Bye!');

            });

            it("stubs a method using a closure", function() {

                Stub::on('kahlan\spec\fixture\plugin\pointcut\Foo')->method('message', function($param) { return $param; });
                $foo = new Foo();
                expect($foo->message('Good Bye!'))->toBe('Good Bye!');

            });

            it("stubs a static method using a closure", function() {

                Stub::on('kahlan\spec\fixture\plugin\pointcut\Foo')->method('::messageStatic', function($param) { return $param; });
                expect(Foo::messageStatic('Good Bye!'))->toBe('Good Bye!');

            });

            it("stubs a magic method multiple times", function() {

                Stub::on('kahlan\spec\fixture\plugin\pointcut\Foo')->method('::magic')->with('hello')->andReturn('world');
                Stub::on('kahlan\spec\fixture\plugin\pointcut\Foo')->method('::magic')->with('world')->andReturn('hello');
                expect(Foo::magic('hello'))->toBe('world');
                expect(Foo::magic('world'))->toBe('hello');

            });

            context("with multiple return values", function(){

                it("stubs a method", function() {

                    Stub::on('kahlan\spec\fixture\plugin\pointcut\Foo')
                        ->method('message')
                        ->andReturn('Good Evening World!', 'Good Bye World!');

                    $foo = new Foo();
                    expect($foo->message())->toBe('Good Evening World!');

                    $foo2 = new Foo();
                    expect($foo2->message())->toBe('Good Bye World!');

                });

            });

            context("with ->methods()", function() {

                it("stubs methods using return values as an array", function() {

                    Stub::on('kahlan\spec\fixture\plugin\pointcut\Foo')->methods([
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

                it("stubs methods using closure", function() {

                    Stub::on('kahlan\spec\fixture\plugin\pointcut\Foo')->methods([
                        'message' => function() {
                            return 'Good Evening World!';
                        },
                        'bar' => function() {
                            return 'Hello Bar!';
                        }
                    ]);

                    $foo = new Foo();
                    expect($foo->message())->toBe('Good Evening World!');

                    $foo2 = new Foo();
                    expect($foo2->bar())->toBe('Hello Bar!');

                });

                it("throw an exception with invalid definition", function() {

                    $closure = function() {
                        $foo = new Foo();
                        Stub::on('kahlan\spec\fixture\plugin\pointcut\Foo')->methods([
                            'bar' => 'Hello Bar!'
                        ]);
                    };
                    $message = "Stubbed method definition for `bar` must be a closure or an array of returned value(s).";
                    expect($closure)->toThrow(new InvalidArgumentException($message));

                });

            });

        });

        context("with a trait", function() {

            it("stubs a method", function() {

                Stub::on('kahlan\spec\fixture\plugin\pointcut\SubBar')
                    ->method('traitMethod')
                    ->andReturn('trait method stubbed !');

                $subBar = new SubBar();
                expect($subBar->traitMethod())->toBe('trait method stubbed !');
                $subBar2 = new SubBar();
                expect($subBar2->traitMethod())->toBe('trait method stubbed !');

            });

        });

    });

    describe("::registered()", function() {

        describe("without provided hash", function() {

            it("returns an empty array when no instance are registered", function() {

                expect(Stub::registered())->toBe([]);

            });

            it("returns an array of registered instances", function() {

                Stub::on('kahlan\spec\fixture\plugin\pointcut\Foo')->method('foo', function() {});

                expect(Stub::registered())->toBeA('array')->toBe([
                    'kahlan\spec\fixture\plugin\pointcut\Foo'
                ]);

            });

        });

        describe("with provided hash", function() {

            it("returns `false` for registered stub", function() {

                expect(Stub::registered('kahlan\spec\fixture\plugin\pointcut\Foo'))->toBe(false);

            });

            it("returns `true` for registered stub", function() {

                Stub::on('kahlan\spec\fixture\plugin\pointcut\Foo')->method('foo', function() {});

                expect(Stub::registered('kahlan\spec\fixture\plugin\pointcut\Foo'))->toBe(true);

            });

        });

    });

    describe("::reset()", function() {

        beforeEach(function() {

            Stub::on('kahlan\spec\fixture\plugin\pointcut\Foo')->method('foo', function() {});
            Stub::on('kahlan\spec\fixture\plugin\pointcut\Bar')->method('bar', function() {});

        });

        it("clears all stubs", function() {

            Stub::reset();
            expect(Stub::registered())->toBe([]);

        });

        it("clears one stub", function() {

            Stub::reset('kahlan\spec\fixture\plugin\pointcut\Foo');
            expect(Stub::registered())->toBe([
                'kahlan\spec\fixture\plugin\pointcut\Bar'
            ]);

        });

    });

    describe("::_generateAbstractMethods()", function() {

        it("throws an exception when called with a non-existing class", function() {

            expect(function() {
                $stub = Stub::classname([
                    'extends' => 'kahlan\plugin\Stub',
                    'methods' => ['::generateAbstractMethods']
                ]);
                Stub::on($stub)->method('::generateAbstractMethods', function($class) {
                    return static::_generateAbstractMethods($class);
                });
                $stub::generateAbstractMethods('some\unexisting\Class');
            })->toThrow(new IncompleteException('Unexisting parent class `some\unexisting\Class`'));

        });

    });

    describe("::create()", function() {

        before(function() {
            $this->is_method_exists = function($instance, $method, $type = "public") {
                if (!method_exists($instance, $method)) {
                    return false;
                }
                $refl = new ReflectionMethod($instance, $method);
                switch($type) {
                    case "static":
                    return $refl->isStatic();
                    break;
                    case "public":
                    return $refl->isPublic();
                    break;
                    case "private":
                    return $refl->isPrivate();
                    break;
                }
                return false;
            };
        });

        it("stubs an instance", function() {

            $stub = Stub::create();
            expect(is_object($stub))->toBe(true);
            expect(get_class($stub))->toMatch("/^kahlan\\\spec\\\plugin\\\stub\\\Stub\d+$/");

        });

        it("names a stub instance", function() {

            $stub = Stub::create(['class' => 'kahlan\spec\stub\MyStub']);
            expect(is_object($stub))->toBe(true);
            expect(get_class($stub))->toBe('kahlan\spec\stub\MyStub');

        });

        it("stubs an instance with a parent class", function() {

            $stub = Stub::create(['extends' => 'kahlan\util\Text']);
            expect(is_object($stub))->toBe(true);
            expect(get_parent_class($stub))->toBe('kahlan\util\Text');

        });

        it("stubs an instance using a trait", function() {

            $stub = Stub::create(['uses' => 'kahlan\spec\mock\plugin\stub\HelloTrait']);
            expect($stub->hello())->toBe('Hello World From Trait!');

        });

        it("stubs an instance implementing some interface", function() {

            $stub = Stub::create(['implements' => ['ArrayAccess', 'Iterator']]);
            $interfaces = class_implements($stub);
            expect(isset($interfaces['ArrayAccess']))->toBe(true);
            expect(isset($interfaces['Iterator']))->toBe(true);
            expect(isset($interfaces['Traversable']))->toBe(true);

        });

        it("stubs an instance with multiple stubbed methods", function() {

            $stub = Stub::create();
            Stub::on($stub)->methods([
                'message' => ['Good Evening World!', 'Good Bye World!'],
                'bar' => ['Hello Bar!']
            ]);

            expect($stub->message())->toBe('Good Evening World!');
            expect($stub->message())->toBe('Good Bye World!');
            expect($stub->bar())->toBe('Hello Bar!');

        });

        it("stubs static methods on a stub instance", function() {

            $stub = Stub::create();
            Stub::on($stub)->methods([
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
            expect(isset($stub->prop))->toBe(true);
            expect(isset($stub->data))->toBe(false);
            unset($stub->data);
            $serialized = serialize($stub);
            unserialize($serialized);
            $string = (string) $stub;
            $stub();
            $stub2 = clone $stub;

        });

        it("defaults stub can be used as container", function() {

            $stub = Stub::create();
            $stub->data = 'hello';
            expect($stub->data)->toBe('hello');

        });

        it("stubs an instance with an extra method", function() {

            $stub = Stub::create([
                'methods' => ['method1']
            ]);

            expect($this->is_method_exists($stub, 'method1'))->toBe(true);
            expect($this->is_method_exists($stub, 'method2'))->toBe(false);
            expect($this->is_method_exists($stub, 'method1', 'static'))->toBe(false);

        });

        it("stubs an instance with an extra static method", function() {

            $stub = Stub::create([
                'methods' => ['::method1']
            ]);

            expect($this->is_method_exists($stub, 'method1'))->toBe(true);
            expect($this->is_method_exists($stub, 'method2'))->toBe(false);
            expect($this->is_method_exists($stub, 'method1', 'static'))->toBe(true);

        });

        it("stubs an instance with an extra method returning by reference", function() {

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
            expect($array)->toBe(['in'/*, 'out'*/]); //I guess that's the limit of the system.

        });

        it("applies constructor parameters to the stub", function () {

            $stub = Stub::create([
                'extends' => 'kahlan\spec\fixture\plugin\stub\ConstrDoz',
                'params' => ['a', 'b']
            ]);

            expect($stub->a)->toBe('a');
            expect($stub->b)->toBe('b');

        });

    });

    describe("::classname()", function() {

        it("stubs class", function() {

            $stub = Stub::classname();
            expect($stub)->toMatch("/^kahlan\\\spec\\\plugin\\\stub\\\Stub\d+$/");

        });

        it("names a stub class", function() {

            $stub = Stub::classname(['class' => 'kahlan\spec\stub\MyStaticStub']);
            expect(is_string($stub))->toBe(true);
            expect($stub)->toBe('kahlan\spec\stub\MyStaticStub');

        });

        it("stubs a stub class with multiple methods", function() {

            $classname = Stub::classname();
            Stub::on($classname)->methods([
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
            Stub::on($classname)->methods([
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

       it("throws an exception with an unexisting trait", function () {

           expect(function() {
               Stub::generate(['uses' => ['an\unexisting\Trait']]);
           })->toThrow(new IncompleteException('Unexisting trait `an\unexisting\Trait`'));

       });

       it("throws an exception with an unexisting interface", function() {

           expect(function() {
               Stub::generate(['implements' => ['an\unexisting\Interface']]);
           })->toThrow(new IncompleteException('Unexisting interface `an\unexisting\Interface`'));

       });

       it("throws an exception with an unexisting parent class", function() {

           expect(function() {
               Stub::generate(['extends' => 'an\unexisting\ParentClass']);
           })->toThrow(new IncompleteException('Unexisting parent class `an\unexisting\ParentClass`'));

       });

       it("overrides the construct method", function() {

            $result = Stub::generate([
                'class' => 'kahlan\spec\plugin\stub\Stub',
                'methods' => ['__construct'],
                'magicMethods' => false
            ]);

            $expected = <<<EOD
<?php
namespace kahlan\\spec\\plugin\\stub;

class Stub {

    public function __construct() {}

}
?>
EOD;
            expect($result)->toBe($expected);

        });

        it("generates use statement", function() {

            $result = Stub::generate([
                'class'      => 'kahlan\spec\plugin\stub\Stub',
                'uses'       => ['kahlan\spec\mock\plugin\stub\HelloTrait'],
                'magicMethods' => false
            ]);

            $expected = <<<EOD
<?php
namespace kahlan\\spec\\plugin\\stub;

class Stub {

    use \\kahlan\\spec\\mock\\plugin\\stub\\HelloTrait;

}
?>
EOD;
            expect($result)->toBe($expected);

        });

        it("generates abstract parent class methods", function() {

            $result = Stub::generate([
                'class'      => 'kahlan\spec\plugin\stub\Stub',
                'extends'    => 'kahlan\spec\fixture\plugin\stub\AbstractDoz'
            ]);

            $expected = <<<EOD
<?php
namespace kahlan\\spec\\plugin\\stub;

class Stub extends \\kahlan\\spec\\fixture\\plugin\\stub\\AbstractDoz {

    public function foo(\$var) {}
    public function bar(\$var1 = NULL, array \$var2 = array()) {}

}
?>
EOD;
            expect($result)->toBe($expected);

        });

        it("generates interface methods", function() {

            $result = Stub::generate([
                'class'        => 'kahlan\spec\plugin\stub\Stub',
                'implements'   => ['Countable'],
                'magicMethods' => false
            ]);

            $expected = <<<EOD
<?php
namespace kahlan\\spec\\plugin\\stub;

class Stub implements \\Countable {

    public function count() {}

}
?>
EOD;
            expect($result)->toBe($expected);

        });

        it("manages methods inheritence", function() {

            $result = Stub::generate([
                'class'      => 'kahlan\spec\plugin\stub\Stub',
                'implements' => ['kahlan\spec\fixture\plugin\stub\DozInterface'],
                'magicMethods' => false
            ]);

            $expected = <<<EOD
<?php
namespace kahlan\\spec\\plugin\\stub;

class Stub implements \\kahlan\\spec\\fixture\\plugin\\stub\\DozInterface {

    public function foo(\$a) {}
    public function bar(\$b = NULL) {}

}
?>
EOD;
            expect($result)->toBe($expected);

            $result = Stub::generate([
                'class'      => 'kahlan\spec\plugin\stub\Stub',
                'extends'    => 'kahlan\spec\fixture\plugin\stub\AbstractDoz',
                'implements' => ['kahlan\spec\fixture\plugin\stub\DozInterface'],
            ]);

            $expected = <<<EOD
<?php
namespace kahlan\\spec\\plugin\\stub;

class Stub extends \\kahlan\\spec\\fixture\\plugin\\stub\\AbstractDoz implements \\kahlan\\spec\\fixture\\plugin\\stub\\DozInterface {

    public function foo(\$var) {}
    public function bar(\$var1 = NULL, array \$var2 = array()) {}

}
?>
EOD;
            expect($result)->toBe($expected);

            $result = Stub::generate([
                'class'      => 'kahlan\spec\plugin\stub\Stub',
                'extends'    => 'kahlan\spec\fixture\plugin\stub\AbstractDoz',
                'implements' => ['kahlan\spec\fixture\plugin\stub\DozInterface'],
                'methods'    => ['foo', 'bar']
            ]);

            $expected = <<<EOD
<?php
namespace kahlan\\spec\\plugin\\stub;

class Stub extends \\kahlan\\spec\\fixture\\plugin\\stub\\AbstractDoz implements \\kahlan\\spec\\fixture\\plugin\\stub\\DozInterface {

    public function foo() {}
    public function bar() {}

}
?>
EOD;
            expect($result)->toBe($expected);

        });

        it("overrides all parent class method and respect typehints using the layer option", function() {

            $result = Stub::generate([
                'class'   => 'kahlan\spec\plugin\stub\Stub',
                'extends' => 'kahlan\spec\fixture\plugin\stub\Doz',
                'layer'   => true
            ]);

            $expected = <<<EOD
<?php
namespace kahlan\\spec\\plugin\\stub;

class Stub extends \\kahlan\\spec\\fixture\\plugin\\stub\\Doz {

    public function foo(\$a) {return parent::foo(\$a);}
    public function foo2(\$b = NULL) {return parent::foo2(\$b);}
    public function foo3(array \$b = array()) {return parent::foo3(\$b);}
    public function foo4(callable \$fct) {return parent::foo4(\$fct);}
    public function foo5(\\Closure \$fct) {return parent::foo5(\$fct);}
    public function foo6(\\Exception \$e) {return parent::foo6(\$e);}
    public function foo7(\\kahlan\\spec\\fixture\\plugin\\stub\\DozInterface \$instance) {return parent::foo7(\$instance);}

}
?>
EOD;
            expect($result)->toBe($expected);

        });

        it("adds ` = NULL` to optional parameter in PHP core method", function() {

            skipIf(defined('HHVM_VERSION'));

            $result = Stub::generate([
                'class'   => 'kahlan\spec\plugin\stub\Stub',
                'extends' => 'LogicException',
                'layer'   => true
            ]);

            $expected = <<<EOD
<?php
namespace kahlan\\\\spec\\\\plugin\\\\stub;

class Stub extends \\\\LogicException {

    public function __construct\\(\\\$message = NULL, \\\$code = NULL, \\\$previous = NULL\\)
EOD;
            expect($result)->toMatch('~' . $expected . '~i');

        });

        it("generates code without PHP tags", function() {

            $result = Stub::generate([
                'class' => 'kahlan\spec\plugin\stub\Stub',
                'magicMethods' => false,
                'openTag' => false,
                'closeTag' => false,
            ]);

            $expected = <<<EOD
namespace kahlan\\spec\\plugin\\stub;

class Stub {



}

EOD;
            expect($result)->toBe($expected);

        });

    });

});
