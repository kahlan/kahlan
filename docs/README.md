# Kahlan
— for Freedom, Truth, and Justice —

* [1 - Why This One?](#why-this-one)
* [2 - Getting Started](#getting-started)
* [3 - Overview](#overview)
* [4 - Matchers](#matchers)
  * [Classic matchers](#classic)
  * [Method invocation matchers](#method)
  * [Argument matchers](#argument)
  * [Custom matchers](#custom)
* [5 - Stubs](#stubs)
  * [Method Stubbing](#method-stubbing)
  * [Instance Stubbing](#instance-stubbing)
  * [Class Stubbing](#class-stubbing)
  * [Custom Stubs](#class-stubs)
* [6 - Monkey Patching](#monkey-patching)
  * [Monkey Patch Quit Statements](#monkey-patch-quit-statements)
* [7 - Reporters](#reporters)
* [8 - Pro Tips](#pro-tips) - including CLI arguments

## <a name="why-this-one"></a>1 - Why This One?

One of PHP's assumptions is that once you define a function/constant/class it stays defined forever. Although this assumption is not really problematic when you are building an application, things get a bit more complicated if you want your application to be easily testable...

**The main test frameworks for PHP are:**
* [PHPUnit](https://phpunit.de) _(which reaches just [23.80% of code coverage after > 10 years of experience in tests](assets/phpunit_4.4_code_coverage.png) by the way)_
* [phpspec](http://phpspec.net)
* [atoum](http://docs.atoum.org)
* [SimpleTest](http://www.simpletest.org)
* [Enhance-PHP](https://github.com/Enhance-PHP/Enhance-PHP)
* etc.

Whilst these "old school frameworks" are considered fairly mature, they don't allow easy testing of hard coded references.

Furthermore, they don't use the `describe-it` syntax either; `describe-it` allows a clean organization of tests to simplify their maintenance (avoiding [this kind of organization](https://github.com/sebastianbergmann/phpunit/tree/master/tests/Regression), for example!). Moreover, the `describe-it` syntax makes tests more reader-friendly (even better than the [atoum fluent syntax organization](https://github.com/atoum/atoum/blob/master/tests/units/classes/asserters/dateInterval.php)

**So what about new test frameworks for PHP ?**

* [Peridot](https://github.com/peridot-php/peridot)
* [pho](https://github.com/danielstjules/pho)
* [Testify](https://github.com/marco-fiset/Testify.php)
* [pecs](https://github.com/noonat/pecs)
* [speciphy](https://github.com/speciphy/speciphy)
* [dspec](https://github.com/davedevelopment/dspec)
* [preview](https://github.com/v2e4lisp/preview)
* etc.

In the list above, although superficially [Peridot](https://github.com/peridot-php/peridot) seems to be mature, really it only provides the basics (i.e the `describe-it` syntax). All other frameworks seems to be some simple proof of concept of the `describe-it` syntax at the time I'm writing this documentation (November 2014).

So, Kahlan was created out of frustration with all existing testing frameworks in PHP. Instead of introducing some new philosophical concepts, tools, java practices or other nonsense, Kahlan focuses on simply providing an environment which allows you to **easily test your code, even with hard coded references**.

To achieve this goal, **Kahlan allows you to stub or monkey patch your code**, just like in Ruby or JavaScript, without any required PECL-extentions. This way, you don't need to put [DI everywhere just to be able to write tests](http://david.heinemeierhansson.com/2012/dependency-injection-is-not-a-virtue.html)!

Some projects like [AspectMock](https://github.com/Codeception/AspectMock) attempted to bring this kind of metaprogramming flexibility for PHPUnit, but Kahlan aims to gather all of these facilities into a full-featured framework boasting a `describe-it` syntax, a lightweight approach and a simple API.

### Main Features

* Simple API
* Small code base (~10 times smaller than PHPUnit)
* Fast Code Coverage metrics ([xdebug](http://xdebug.org) required)
* Handy stubbing system (no more [mockery](https://github.com/padraic/mockery) or [prophecy](https://github.com/phpspec/prophecy) no longer needed)
* Set stubs on your class methods directly (i.e allows dynamic mocking)
* Ability to Monkey Patch your code (i.e. allows replacement of core functions/classes on the fly)
* Check called methods on your class/instances
* Built-in Reporters/Exporters (Terminal, Coveralls, Code Climate, Scrutinizer, Clover)
* Extensible, customizable workflow

**PS:**
All of these features work with the [Composer](https://getcomposer.org/) autoloader out of the box, but if you want to make it work with your own autoloader, you will need to create your own `Interceptor` class for it (which thankfully is pretty trivial! ;-) ).

## <a name="getting-started"></a>2 - Getting started

**Requirement: Just before continuing, make sure you have installed [Composer](https://getcomposer.org/).**

To make a long story short let's take [the following repository](https://github.com/crysalead/string) as an example.

It's a simple string class in PHP which give you a better understanding on how to structure a project to be easily testable with Kahlan.

Here is the tree structure of this project:

```
├── bin
├── .gitignore
├── .scrutinizer.yml           # Optional, it's for using https://scrutinizer-ci.com
├── .travis.yml                # Optional, it's for using https://travis-ci.org
├── composer.json              # Need at least the Kahlan dependency
├── LICENSE.txt
├── README.md
├── spec                       # The directory which contain specs
│   └── string
│       └── StringSpec.php
├── src                        # The directory which contain sources code
│   └── String.php
```

To start playing with it you'll need to:

```bash
git clone git@github.com:crysalead/string.git
cd string
composer install
```

And then run the tests (referred to as 'specs') with:

```bash
./bin/kahlan --coverage=4
```

**Note:** the `--coverage=4` option is optional.

You are now able to build your own project with a suite of Kahlan specs by following the above structure.

## <a name="overview"></a>3 - Overview

### Describe Your Specs

Because test organization is one of the key point of keeping clean and maintainable tests, Kahlan allow to group tests syntactically using a closure syntax.

```php
describe("ToBe", function() {

    describe("::match()", function() {

        it("passes if true === true", function() {

            expect(true)->toBe(true);

        });

    });

});
```

* `describe`: generally contains all specs for a method. Using the class method's name is probably the best option for a clean description.
* `context`: is used to group tests related to a specific use case. Using "when" or "with" followed by the description of the use case is generally a good practice.
* `it`: contains the code to test. Keep its description short and clear.

### Setup and Teardown

As the name implies, the `beforeEach` function is called once before **each** spec contained in a `describe`.

```php
describe("Setup and Teardown", function() {

    beforeEach(function() {
        $this->foo = 1;
    });

    describe("Nested level", function() {

        beforeEach(function() {
            $this->foo++;
        });

        it("expects that the foo variable is equal to 2", function() {

            expect($this->foo)->toBe(2);

        });

    });

});
```

Setup and Teardown functions can be used at any `describe` or `context` level:

* `before`: Run once inside a `describe` or `context` before all contained specs.
* `beforeEach`: Run before each spec of the same level.
* `afterEach`: Run after each spec of the same level.
* `after`: Run once inside a `describe` or `context` after all contained specs.

### Expectations

Expectations are built using the `expect` function which takes a value, called the **actual**, as parameter and chained with a matcher function taking the **expected** value and some optional extra arguments as parameters.

```php
describe("Positive Expectation", function() {

    it("expects that 5 > 4", function() {

        expect(5)->toBeGreaterThan(4);

    });

});
```

You can find [all built-in matchers here](#matchers).

### Negative Expectations

Any matcher can be evaluated negatively by chaining `expect` with `not` before calling the matcher:

```php
describe("Negative Expectation", function() {

    it("doesn't expect that 4 > 5", function() {

        expect(4)->not->toBeGreaterThan(5);

    });

});
```

### Variable scope

You can use `$this` for making a variable **available** for a sub scope:

```php
describe("Scope inheritance", function() {

    beforeEach(function() {
        $this->foo = 5;
    });

    it("accesses variable defined in the parent scope", function() {

        expect($this->foo)->toEqual(5);

    });

});
```

You can also play with scope's data inside closures:

```php
describe("Scope inheritance & closure", function() {

    it("sets a scope variables inside a closure", function() {

        $this->closure = function() {
            $this->foo = 'bar';
        };
        $this->closure();
        expect($this->foo)->toEqual('bar');

    });

    it("gets a scope variable inside closure", function() {

        $this->foo = 'bar';
        $this->closure = function() {
            return $this->foo;
        };
        expect($this->closure())->toEqual('bar');

    });

});
```

#### Scope isolation

**Note:** A variable assigned with `$this` inside either a `describe/context` or an `it` will **not** be available to a parent scope.

```php
describe("Scope isolation", function() {

    it("sets a variable in the scope", function() {

        $this->foo = 2;
        expect($this->foo)->toEqual(2);

    });

    it("doesn't find any foo variable in the scope", function() {

        expect(isset($this->foo))->toBe(false);

    });

});
```

## <a name="matchers"></a>4 - Matchers

* [Classic matchers](#classic)
* [Method invocation matchers](#method)
* [Argument matchers](#argument)
* [Custom matchers](#custom)

### <a name="classic"></a>Classic matchers

**Note:** Expectations can only be done inside `it` blocks.

**toBe($expected)**

```php
it("passes if $actual === $expected", function() {

    expect(true)->toBe(true);

});
```

**toEqual($expected)**

```php
it("passes if $actual == $expected", function() {

    expect(true)->toEqual(1);

});
```

**toBeTruthy()**

```php
it("passes if $actual is truthy", function() {

    expect(1)->toBeTruthy();

});
```

**toBeFalsy() / toBeEmpty()**

```php
it("passes if $actual is falsy", function() {

    expect(0)->toBeFalsy();
    expect(0)->toBeEmpty();

});
```

**toBeNull()**

```php
it("passes if $actual is null", function() {

    expect(null)->toBeNull();

});
```

**toBeA()**

```php
it("passes if $actual is of a specific type", function() {

    expect('Hello World!')->toBeA('string');
    expect(false)->toBeA('boolean');
    expect(new stdClass())->toBeA('object');

});
```

**toBeAnInstanceOf()**

```php
it("passes if $actual is an instance of stdObject", function() {

    expect(new stdClass())->toBeAnInstanceOf('stdObject');

});
```

**toHaveLength()**

```php
it("passes if $actual has the correct length", function() {

    expect('Hello World!')->toHaveLength(12);
    expect(['a', 'b', 'c'])->toHaveLength(3);

});
```

**toContain($expected)**

```php
it("passes if $actual contain $expected", function() {

    expect([1, 2, 3])->toContain(3);

});
```

**toBeCloseTo($expected)**

```php
it("passes if abs($actual - $expected)*2 < 0.01", function() {

    expect(1.23)->toBeCloseTo(1.225, 2);
    expect(1.23)->not->toBeCloseTo(1.2249999, 2);

});
```

**toBeGreaterThan($expected)**

```php
it("passes if $actual > $expected", function() {

    expect(1)->toBeGreaterThan(0.999);

});
```

**toBeLessThan($expected)**

```php
it("passes if $actual < $expected", function() {

    expect(0.999)->toBeLessThan(1);

});
```

**toThrow($expected)**

```php
it("passes if $closure throws the $expected exception", function() {

    $closure = function() {
        // place the code that you expect to throw an exception in a closure, like so
        throw new RuntimeException('exception message');
    };
    expect($closure)->toThrow();
    expect($closure)->toThrow(new RuntimeException());
    expect($closure)->toThrow(new RuntimeException('exception message'));

});
```

**toMatch($expected)**

```php
it("passes if $actual matches the $expected regexp", function() {

    expect('Hello World!')->toMatch('/^H(.*?)!$/');

});
```

```php
it("passes if $actual matches the $expected closure logic", function() {

    expect('Hello World!')->toMatch(function($actual) {
        return $actual === 'Hello World!';
    });

});
```

**toEcho($expected)**

```php
it("passes if $closure echoes the expected output", function() {

    $closure = function() {
        echo "Hello World!";
    };
    expect($closure)->toEcho("Hello World!");

});
```

### <a name="method"></a>Method invocation matchers

**toReceive($expected)**

```php
it("expects $foo to receive message() with the correct param", function() {

    $foo = new Foo();
    expect($foo)->toReceive('message')->with('My Message');
    $foo->message('My Message');

});
```
```php
it("expects $foo to receive ::message() with the correct param", function() {

    $foo = new Foo();
    expect($foo)->toReceive('::message')->with('My Message');
    $foo::message('My Message');

});
```

**toReceiveNext($expected)**

```php
it("expects $foo to receive message() followed by foo()", function() {

    $foo = new Foo();
    expect($foo)->toReceive('message');
    expect($foo)->toReceiveNext('foo');
    $foo->message();
    $foo->foo();

});
```
```php
it("expects $foo to receive message() but not followed by foo()", function() {

    $foo = new Foo();
    expect($foo)->toReceive('message');
    expect($foo)->not->toReceiveNext('foo');
    $foo->foo();
    $foo->message();

});
```

### <a name="argument"></a>Argument Matchers

To enable **Argument Matching** add the following `use` statement in the top of your tests:

```php
use kahlan\Arg;
```

With the `Arg` class you can use any existing matchers to test arguments.

```php
it("expects params to match the argument matchers", function() {

    $foo = new Foo();
    expect($foo)->toReceive('message')->with(Arg::toBeA('boolean'));
    expect($foo)->toReceiveNext('message')->with(Arg::toBeA('string'));
    $foo->message(true);
    $foo->message('Hello World!');

});
```
```php
it("expects params match the toContain argument matcher", function() {

    $foo = new Foo();
    expect($foo)->toReceive('message')->with(Arg::toContain('My Message'));
    $foo->message(['My Message', 'My Other Message']);

});
```

### <a name="custom"></a>Custom matchers

With Kahlan you can easily create you own matchers. Long story short, a matcher is a simple class with a least a two static methods: `match()` and `description()`.

Example of a `toBeZero()` matcher:

```php
namespace my\namespace;

class ToBeZero
{

    public static function match($actual, $expected = null)
    {
        return $actual === 0;
    }

    public static function description()
    {
        return "be equal to 0.";
    }
}
```

Once created you only need to [register it](#config-file) using the following syntax:

```php
kahlan\Matcher::register('toBeZero', 'my\namespace\ToBeZero');
```

**Note:** custom matcher should be reserved to frequently used matching. For other cases, just use the `toMatch` matcher using the matcher closure as parameter.


## <a name="stubs"></a>5 - Stubs

To enable **Method Stubbing** add the following `use` statement in the top of your specs:

```php
use kahlan\plugin\Stub;
```

### <a name="method-stubbing"></a>Method Stubbing

`Subs::on()` can stub any existing methods on any class.

```php
it("stubs a method", function() {

    $instance = new MyClass();
    Stub::on($instance)->method('myMethod')->andReturn('Good Morning World!');
    expect($instance->myMethod())->toBe('Good Morning World!');

});
```

You can stub subsequent calls to different return values:

```php
it("stubs a method with multiple return values", function() {

    $instance = new MyClass();
    Stub::on($instance)->method('sequential')->andReturn(1, 3, 2);
    expect($instance->myMethod())->toBe(1);
    expect($instance->myMethod())->toBe(3);
    expect($instance->myMethod())->toBe(2);

});
```

You can also stub `static` methods using `::`:

```php
it("stubs a static method", function() {

    $instance = new MyClass();
    Stub::on($instance)->method('::myMethod')->andReturn('Good Morning World!');
    expect($instance::myMethod())->toBe('Good Morning World!');

});
```

And it's also possible to use a closure directly:

```php
it("stubs a method using a closure", function() {

    Stub::on($foo)->method('message', function($param) { return $param; });

});
```

You can use the `methods()` method for reducing verbosity:

```php
it("stubs many methods", function() {

    $instance = new MyClass();
    Stub::on($instance)->methods([
        'message' => ['Good Morning World!', 'Good Bye World!'],
        'bar' => ['Hello Bar!']
    ]);

    expect($instance->message())->toBe('Good Morning World!');
    expect($instance->message())->toBe('Good Bye World!');
    expect($instance->bar())->toBe('Hello Bar!');

});
```

**Note:** Using the `'bar' => 'Hello Bar!'` syntax is not allowed here. Indeed, direct assignation is considered as a closure definition. For example, in `'bar' => function() {return 'hello'}` the closure is considered as the method definition for the `bar()` method. On the other hand, with `'bar' => [function() {return 'hello'}]`, the closure will be the return value of the `bar()` method.

### <a name="instance-stubbing"></a>Instance Stubbing

When you are testing an application, sometimes you need a simple, polyvalent instance for receiving a couple of calls. `Stub::create()` can create such polyvalent instance:

```php
it("generates a polyvalent instance", function() {

    $stub = Stub::create();
    expect(is_object($stub))->toBe(true);
    expect($stub->something())->toBe(null);

});
```

**Note:** Generated stubs implements by default `__call()`, `__callStatic()`,`__get()`, `__set()` and some other magic methods for a maximum of polyvalence.

So by default `Stub::on()` can be applied on any method name. Indeed `__call()` will catch everything. However, you should pay attention that `method_exists` won't work on this "virtual method stubs".

To make it works, you will need to add the necessary "endpoint(s)" using the `'methods'` option like in the following example:

```php
it("stubs a static method", function() {

    $stub = Stub::create(['methods' => ['myMethod']]); // Adds the method `'myMethod'` as an existing "endpoint"
    expect(method_exists($stub, 'myMethod'))->toBe(true); // It works !

});
```

### <a name="class-stubbing"></a>Class Stubbing

You can also create class names (i.e a string) using `Stub::classname()`:

```php
it("generates a polyvalent class", function() {

    $class = Stub::classname();
    expect(is_string($stub))->toBe(true);

    $stub = new $class()
    expect($stub)->toBeAnInstanceOf($class);

});
```

### <a name="custom-stubbing"></a>Custom Stubs

There are also a couple of options for creating some stubs which inherit a class, implement interfaces or use traits.

An example using `'extends'`:

```php
it("stubs an instance with a parent class", function() {

    $stub = Stub::create(['extends' => 'string\String']);
    expect(is_object($stub))->toBe(true);
    expect(get_parent_class($stub))->toBe('string\String');

});
```
**Tip:** If you extends from an abstract class, all missing methods will be automatically added to your stub.

**Note:** If the `'extends'` option is used, magic methods **won't be included**, so as to to avoid any conflict between your tested classes and the magic method behaviors.

However, if you still want to include magic methods with the `'extends'` option, you can manually set the `'magicMethods'` option to `true`:

```php
it("stubs an instance with a parent class and keeps magic methods", function() {

    $stub = Stub::create([
        'extends'      => 'string\String',
        'magicMethods' => true
    ]);

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
```

An other example using `'implements'`:

```php
it("stubs an instance implementing some interfaces", function() {

    $stub = Stub::create(['implements' => ['ArrayAccess', 'Iterator']]);
    $interfaces = class_implements($stub);
    expect($interfaces)->toHaveLength(3);
    expect(isset($interfaces['ArrayAccess']))->toBe(true);
    expect(isset($interfaces['Iterator']))->toBe(true);
    expect(isset($interfaces['Traversable']))->toBe(true); //Comes with `'Iterator'`

});
```

A last example using `'uses'` to test your traits directly:

```php
it("stubs an instance using a trait", function() {
    $stub = Stub::create(['uses' => 'spec\mock\plugin\stub\HelloTrait']);
    expect($stub->hello())->toBe('Hello World From Trait!');
});
```

## <a name="monkey-patching"></a>6 - Monkey Patching

To enable **Monkey Patching**, add the following `use` statement in the top of your specs:

```php
use kahlan\plugin\Monkey;
```

Monkey Patching allows replacement of core functions and classes that can't be stubbed, for example `[time()](http://php.net/manual/en/function.time.php)`, `[DateTime](http://php.net/manual/en/class.datetime.php)` or `[MongoId](http://php.net/manual/en/class.mongoid.php)` for example.

With Kahlan, you can patch anything you want using `Monkey::patch()`!

For example, I have the following class which needs to be patched:

```php
namespace kahlan\monkey;

use DateTime;

class Foo
{
    public function time()
    {
        return time();
    }

    public function datetime($datetime = 'now')
    {
        return new DateTime($datetime);
    }
}
```

You can patch the `time()` function on the fly like in the following spec:

```php
namespace spec;

use kahlan\monkey\Foo;

function mytime() {
    return 245026800;
}

describe("Monkey patching", function() {

    it("patches a core function", function() {

        $foo = new Foo();
        Monkey::patch('time', 'spec\mytime');
        expect($foo->time())->toBe(245026800);

    });

});
```

Unbelievable, right? Moreover, you can also replace the `time()` function by a simple closure:

```php
it("patches a core function with a closure", function() {

    $foo = new Foo();
    Monkey::patch('time', function(){return 123;});
    expect($foo->time())->toBe(123);

});
```

Using the same syntax, you can also patch any core classes by just monkey patching a fully-namespaced class name to another fully-namespaced class name.

You can find [another example of how to use Monkey Patching here](https://github.com/warrenseymour/kahlan-lightning-talk).

### <a name="monkey-patch-quit-statements"></a>Monkey Patch Quit Statements

When a unit test exercises code that contains an `exit()` or a `die()` statement, the execution of the whole test suite is aborted. With Kahlan, you can make all quit statements (i.e. like `exit()` or `die()`) throw a `QuitException` instead of quitting the test suite for real.

To enable **Monkey Patching on Quit Statements** add the following `use` statements in the top of your tests:

```php
use kahlan\QuitException;
use kahlan\plugin\Quit;
```

And then use `Quit::disable()` like in the following:
```php
it("throws an exception when an exit statement occurs if not allowed", function() {
    Quit::disable();

    $closure = function() {
        $foo = new Foo();
        $foo->runCodeWithSomeQuitStatementInside(-1);
    };

    expect($closure)->toThrow(new QuitException('Exit statement occurred', -1));
});
```

**Note:** This only work **for classes loaded by Composer**. If you try to create a stub with a `exit()` statement inside a closure it won't get intercepted by patchers and the application will quit for real. Indeed, **code in `*Spec.php` files are not intercepted and patched**.

## <a name="reporters"></a>7 - Reporters

Kahlan provides a flexible reporter system which can be extended easily.

There are three build-in reporters and the default is the dotted one:

```php
./bin/kahlan --reporter=dot # Default value
```

To use a reporter which looks like more a progress bar use the following option:
```php
./bin/kahlan --reporter=bar
./bin/kahlan --reporter=verbose
```

You can easily roll you own if these reporters don't fit your needs.

For example, if you want a console based reporter, create a PHP class which extends `kahlan\reporter\Terminal`. The `Terminal` class offers some useful methods like `write()` for doing some echos on the terminal. But if you wanted to create some kind of JSON reporter extending from `kahlan\reporter\Reporter` would be enough.

Example of a custom console reporter:
```php
<?php
namespace my\namespace;

class MyReporter extends \kahlan\reporter\Terminal
{
    /**
     * Callback called on successful expectation.
     *
     * @param array $report The report array.
     */
    public function pass($report = [])
    {
        $this->write('✓', "green");
    }

    /**
     * Callback called on failure.
     *
     * @param array $report The report array.
     */
    public function fail($report = [])
    {
        $this->write('☠', "red");
        $this->write("\n");
        $this->_report($report);
    }

    /**
     * Callback called when an exception occur.
     *
     * @param array $report The report array.
     */
    public function exception($report = [])
    {
        $this->write('☠', "magenta");
        $this->write("\n");
        $this->_report($report);
    }

    /**
     * Callback called on a skipped spec.
     *
     * @param array $report The report array.
     */
    public function skip($report = [])
    {
        $this->write('-', "cyan");
    }

    /**
     * Callback called at the end of specs processing.
     */
    public function end($results = [])
    {
        $this->write("\n");
        $this->_summary($results);
        $this->_exclusive($results);
    }
}
?>
```

**Note:** `_report()` and `_summary()` are also two inherited methods. Their roles are to format errors and to display a summary of passed tests respectively. Feel free to dig into the source code if you want some more specific output for that.

The next step is to register your new reporter so you'll need to create you own custom [config file](#config-file)).

Example of config file:
```php
<?php
use filter\Filter;
use my\namespace\reporter\MyReporter;

// The logic to inlude into the workflow.
Filter::register('kahlan.myconsole', function($chain) {
    $reporters = $this->reporters();
    $reporters->add('myconsole', new MyReporter(['start' => $this->_start));
});

// Apply our logic to the `'console'` entry point.
Filter::apply($this, 'console', 'kahlan.myconsole');
?>
```

`$this->_start` is the timestamp in micro seconds of when the process has been started. If passed to reporter, it'll be able to display an accurate execution time.

**Note:** `'myconsole'` is an arbitrary name, it can be anything.

Let's run it:
```php
./bin/kahlan --config=my-config.php
```
![custom_reporter](assets/custom_reporter.png)

A bit ugly, but the check marks and the skulls are present.

## <a name="pro-tips"></a>8 - Pro Tips

### Use the `--ff` option (fast fail)

`--ff` is the fast fail option. If used, the test suite will be stopped as soon as a failing test occurs. You can also specify a number of "allowed" fails before stopping the process. For example:

```
./bin/kahlan --ff=3
```

will stop the process as soon as 3 specs `it` failed.

### Use `--coverage` option

Kahlan has some built-in code coverage exporter (e.g. Coveralls & Scrutinizer exporters) but it can also be used to generates some detailed code coverage report directly inside the console.

**`--coverage=<integer>`** will generates some code coverage summary depending on the passed integer.

* 0: no coverage
* 1: code coverage summary of the whole project
* 2: code coverage summary detailed by namespaces
* 3: code coverage summary detailed by classes
* 4: code coverage summary detailed by methods

However sometimes it's interesting to see in details all covered/uncovered lines. To achieve this, you can pass a string to the `--coverage` option.

**`--coverage=<string>`** will generates some detailed code coverage according to the specified namespace, class or method definition.

Example:

```php
./bin/kahlan --coverage="kahlan\reporter\coverage\driver\Xdebug::stop()"
```

Will give you the detailed code coverage of the `Xdebug::stop()` method.

![code_coverage_example](assets/code_coverage_example.png)

**Note:**
All available namespaces, classed or methods definitions can be extracted from a simple `--coverage=4` code coverage summary.

### Use the exclusive mode

When writing your tests sometimes you want to **only execute** the test(s) you are working on. For this, you can prefix your spec by doubling the first letter like in the following example:

```php
describe("test exclusive mode", function() {

    it("will be ignored", function() {
    });

    it("will be ignored", function() {
    });

    iit("will be runned", function() {
    });
});
```

If you want to run a subset instead of a single test you can use `ddescribe` or `ccontext` instead.

**Tip:** combined with `--coverage=<string>` this is a powerful combo to see exactly what part of the code is covered for a subset of specs only.

### Comment out a spec

To comment out a spec, you can use the `x` prefix i.e. `xdescribe`, `xcontext` or `xit`.

## <a name="config-file"></a>The Kahlan config file

If you want to set some default options, change the execution workflow or load some custom plugins at a boostrap level, you will need to setup you own config file.

Kahlan attempt to load the `kahlan-config.php` file from the current directory as the default config file. However you can define your own path using the `--config=myconfigfile.php` option in the command line. Custom `--config` can be useful if you want to use some specific configuration for Travis or something else.

Example of a config file:

```php
<?php
use filter\Filter;
use kahlan\reporter\coverage\exporter\Coveralls;

// It overrides some default option values.
// Note that the values passed in command line will overwrite the ones below.
$args = $this->args();
$args->argument('ff', 'default', 1);
$args->argument('coverage', 'default', 3);
$args->argument('coverage-scrutinizer', 'default', 'scrutinizer.xml');
$args->argument('coverage-coveralls', 'default', 'coveralls.json');

// The logic to include into the workflow.
Filter::register('kahlan.coveralls', function($chain) {

    // Get the reporter called `'coverage'` from the list of reporters
    $coverage = $this->reporters()->get('coverage');

    // Abort if no coverage is available.
    if (!$coverage || !$this->args()->exists('coverage-coveralls')) {
        return $chain->next();
    }

    // Use the `Coveralls` class to write the JSON coverage into a file
    Coveralls::write([
        'coverage' => $coverage,
        'file' => $this->args()->get('coverage-coveralls'),
        'service_name' => 'travis-ci',
        'service_job_id' => getenv('TRAVIS_JOB_ID') ?: null
    ]);

    // Continue the chain
    return $chain->next();
});

// Apply the logic to the `'reporting'` entry point.
Filter::apply($this, 'reporting', 'kahlan.coveralls');
?>
```

Above `'kahlan.coveralls'` is just a custom name and could be whatever as long as `Filter::register()` and `Filter::apply()` are consistent on the namings.

`$this` refer to the Kahlan instance so `$this->reporters()->get('coverage')` will give you the instance of the coverage reporter. This coverage reporter will contain all raw data which is passed to the `Coveralls` exporter to be formatter.

For more information about filters, take a look at [the documentation of the filter library](https://github.com/crysalead/filter).

The filterable entry points are the following:

* `'workflow`'           # The one to rule them all
  * `'interceptor`'      # Operations on the autoloader
  * `'namespaces`'       # Adds some namespaces not managed by composer (like `spec`)
  * `'patchers`'         # Adds patchers
  * `'loadSpecs`'        # Loads specs
  * `'reporters`'        # Adds reporters
    * `'console'`        # Creates the console reporter
    * `'coverage'`       # Creates the coverage reporter
  * `'matchers`'         # Useful for registering some further matchers
  * `'run`'              # Runs the test suite
  * `'reporting`'        # Runs some additional reporting tasks
  * `'stop`'             # Trigger the stop event to reporters
  * `'quit`'             # For some additional post processing before quitting


[You can see more details about how the workflow works here](https://github.com/crysalead/kahlan/blob/master/src/cli/Kahlan.php) (start reading with the `run()` method).

### Optimizations

Kahlan acts like a wrapper. It intercepts loaded classes Just It Time (i.e. during the autoloading step) and rewrites the source code on the fly to make it easily testable with PHP. That's why Monkey Patching or redefining a class's method can be done inside the testing environment without any PECL extensions like runkit, aop, etc.

Notice that this approach will make your code run a bit slower than your original code. However you can optimize Kahlan's interceptor to only patch the namespaces you want:

For example, the following configuration will only limit the patching to a bunch of namespaces/classes:

```php
$this->args()->set('include', [
    'myapp',
    'lithium',
    'li3_zendserver\data\Job',
    'AuthorizeNetCIM'
]);
```

Conversely you can also exclude some external dependencies to speed up performances if you don't intend to Monkey Patch/Stub some namespaces/classes:
```php
$this->args()->set('exclude', [
    'Symfony',
    'Doctrine'
]);
```

Finally you can also disable all the patching everywhere if you prefer to deal with DI only and are not interested by Kahlan's features:
```php
$this->args()->set('include', []);
```
**Note:** You will still able to stub instances and classes created with `Stub::create()`/`Stub::classname()` anyway.
