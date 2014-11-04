# Kahlan
— for Freedom, Truth, and Justice —

* [1 - Why This One ?](#why-this-one)
* [2 - Getting Started](#getting-started)
* [3 - Overview](#overview)
* [4 - Matchers](#matchers)
  * [Classic matchers](#classic)
  * [Method invocation matchers](#method)
  * [Argument matchers](#argument)
  * [Custom matchers](#custom)
* [5 - Stubs](#stubs)
  * [Method Stubing](#method-stubing)
  * [Instance Stubing](#instance-stubing)
  * [Class Stubing](#class-stubing)
* [6 - Monkey Patching](#monkey-patching)
  * [Monkey Patch Quit Statements](#monkey-patch-quit-statements)
* [7 - Reporters](#reporters)
* [8 - Pro Tips](#pro-tips)

## <a name="why-this-one"></a>1 - Why This One ?

One of PHP's assumption is that once you define a function/constant/class it stays defined forever. If this assumption is not really problematic when you are building an application, things get a bit more complicated when you want to unit test your application easily.

**So what are the existing test platform for PHP ?**
* [PHPUnit](https://phpunit.de) which reaches [23.80% of code coverage after > 10 years of experience](assets/phpunit_4.4_code_coverage.png) in tests by the way
* [phpspec](http://phpspec.net)
* [atoum](http://docs.atoum.org)
* [SimpleTest](http://www.simpletest.org)
* [Enhance-PHP](https://github.com/Enhance-PHP/Enhance-PHP)
* etc.

If all these "old school frameworks" are mature enough, they don't allow to test hard coded references easily. And the fact that they don't use the `describe-it` syntax either doesn't allow a clean organization of tests to simplify their maintenance (and avoiding [this kind of organization](https://github.com/sebastianbergmann/phpunit/tree/master/tests/Regression) for example). Moreover the `describe-it` syntax makes tests more reader-friendly (i.e even better than the[atoum fluent syntax organization](https://github.com/atoum/atoum/blob/master/tests/units/classes/asserters/dateInterval.php)

**So what about new frameworks for PHP ?**

* [Peridot](https://github.com/peridot-php/peridot)
* [pho](https://github.com/danielstjules/pho)
* [Testify](https://github.com/marco-fiset/Testify.php)
* [pecs](https://github.com/noonat/pecs)
* [speciphy](https://github.com/speciphy/speciphy)
* [dspec](https://github.com/davedevelopment/dspec)
* [preview](https://github.com/v2e4lisp/preview)
* etc.

In these list above, if [Peridot](https://github.com/peridot-php/peridot) seems to be mature enough it only provides the basics (i.e the `describe-it` syntax) and all others seems to be some proof of concept only of the `describe-it` syntax at the time I'm writing this documentation.

So Kahlan was created out of frustration with all existing testing frameworks in PHP. Instead of introducing some new philosophical concepts, tools, java practices, craps, Kahlan just provide an environment which allow you to **easily test your code even with hard coded references**.

To achieve this goal **Kahlan allow to stub or monkey patch your code** like in Ruby or JavaScript without any required PECL-extentions. That way you won't need to put some [DI everywhere just for being able to write a test](http://david.heinemeierhansson.com/2012/dependency-injection-is-not-a-virtue.html).

Some projects like [AspectMock](https://github.com/Codeception/AspectMock) attempted to bring this kind of metaprogramming flexibility for PHPUnit but Kahlan aimed to gather all this facilities in a full-featured framework using a `describe-it` syntax, a lightweight approach and a simple API.

### Main Features

* Small API
* Small code base (~5k loc)
* Complete Code Coverage metrics support (xdebug required)
* Set stubs on your class methods directly (i.e allow dynamic mocking).
* Allow to Monkey Patch your code (ie. allows replacement of core functions/classes on the fly).
* Check called methods on your class/instances.
* Built-in Reporters/Exporters (Terminal, Coveralls, Scrutinizers)
* An extensible & customizable workflow

**PS:**
All this features works with the [Composer](https://getcomposer.org/) autoloader out of the box, but if you want to make it works with your own autoloader you will need to create your own `Interceptor` class for it (however it's prettry trivial).

## <a name="getting-started"></a>2 - Getting started

**Requirement: Just before continuing, make sure you have installed [Composer](https://getcomposer.org/).**

To make a long story short let's take [the following repository](https://github.com/crysalead/string) as an example.

It's a simple string class in PHP which give you a better understanding on how to structure a project to be easily testable with Kahlan.

Below the detailed tree structure of this project:

```
├── bin
├── .gitignore
├── .scrutinizer.yml           # Optionnal, it's for using https://scrutinizer-ci.com
├── .travis.yml                # Optionnal, it's for using https://travis-ci.org
├── composer.json              # Need at least the Khalan dependency
├── LICENSE.txt
├── README.md
├── spec                       # The directory which contain specs
│   └── string
│       └── StringSpec.php
├── src                        # The directory which contain sources code
│   └── String.php
```

To start playing with it you'll need to:

```
git clone git@github.com:crysalead/string.git
cd string
composer install
```

And then run specs with:
```
./bin/kahlan --coverage=4
```

**Note:** the `--coverage=4` option is of course optionnal.

You are now able to build you own project by following the above structure.

## <a name="overview"></a>3 - Overview

### Describe Your Specs

Because test's organization is one of the key point of keeping clean and maintainable tests, Kahlan allow to group tests syntaxically using a closure syntax.

```php
describe("ToBe", function() {

    describe("::match()", function() {

        it("passes if true === true", function() {

            expect(true)->toBe(true);

        });

    });

});
```

* `describe`: generally contains all specs for a method. Using the class method's name is probably the better option for a clean description.
* `context`: is used to group tests realated to a specific use case. Using "when" or "with" followed by the description of the use case is generally a good practice.
* `it`: contains the code to test. Keep its description short and clear.

### Setup and Teardown

As the name implies the `beforeEach` function is called once before **each** spec contained in a `describe`.

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

* `before`: Runned once inside a `describe` or `context` before all contained specs.
* `beforeEach`: Runned before each specs of the same level.
* `afterEach`: Runned after each specs of the same level.
* `after`: Runned once inside a `describe` or `context` after all contained specs.

### Expectations

Expectations are built using the `expect` function which takes a value, called the **actual**, as parameter and chained with a matcher function taking the **expected** value and some optionnal extra arguments as parameter.

```php
describe("Positive Expectation", function() {

    it("expects that 5 > 4", function() {

        expect(5)->toBeGreaterThan(4);

    });

});
```

You can find [all built-in matchers here](#matchers).

### Negative Expectations

Any matcher can be evaluated negatively by chaining `expect` with `not` before calling the matcher.

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

**Note:** A variable setted with `$this` inside a `describe/context` or a `it` will **not** be available in a parent scope.

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
it("passes if $actual throws the $expected exception", function() {

    $closure = function() {
        throw new RuntimeException('exception message');
    };
    expect($closure)->toThrow();
    expect($closure)->toThrow(new RuntimeException());
    expect($closure)->toThrow(new RuntimeException('exception message'));

});
```

**toEcho($expected)**

```php
it("passes if $actual throws the $expected exception", function() {

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

With Kahlan you can easily create you own matchers. Long story short, a matcher is a simple class with a least a two static methods: `match()` & `description()`.

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

## <a name="stubs"></a>5 - Stubs

To enable **Method Stubbing** add the following `use` statement in the top of your specs:

```php
use kahlan\plugin\Stub;
```

### <a name="method-stubing"></a>Method Stubbing

`Subs::on()` can stub any existing methods on any class.

```php
it("stubs a method", function() {

    $instance = new MyClass();
    Stub::on($instance)->method('myMethod')->andReturn('Good Morning World!');
    expect($instance->myMethod())->toBe('Good Morning World!');

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

You can use an array based syntax for reducing verbosity:

```php
it("stubs many methods", function() {

    $instance = new MyClass();
    Stub::on($instance)->method([
        'message' => ['Good Morning World!', 'Good Bye World!'],
        'bar' => ['Hello Bar!']
    ]);

    expect($instance->message())->toBe('Good Morning World!');
    expect($instance->message())->toBe('Good Bye World!');
    expect($instance->bar())->toBe('Hello Bar!');

});
```

And it's also possible to use a closure directly:

```php
it("stubs a method using a closure", function() {

    Stub::on($foo)->method('message', function($param) { return $param; });

});
```

### <a name="instance-stubing"></a>Instance Stubbing

When you are testing an application, sometimes you need a simple & polyvalent instance for receiving a couple of calls. `Stub::create()` can create such polyvalent instance:

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

### <a name="class-stubing"></a>Class Stubbing

You can also create some class names (i.e a string) using `Stub::classname()`:

```php
it("generates a polyvalent class", function() {

    $class = Stub::classname();
    expect(is_string($stub))->toBe(true);

    $stub = new $class()
    expect($stub)->toBeAnInstanceOf($class);

});
```

### Custom Stubs

There's also a couple of options for creating some stubs which inherit a class, implement interfaces or use traits.

An example using `'extends'`:
```php
it("stubs an instance with a parent class", function() {

    $stub = Stub::create(['extends' => 'string\String']);
    expect(is_object($stub))->toBe(true);
    expect(get_parent_class($stub))->toBe('string\String');

});
```
**Tip:** If you extends from an abstract class, all missing methods will be automatically added to your stub.

**Note:** If the `'extends'` option is used, all magic methods **won't be included** to avoid any conflict between your tested classes and the magic method behaviors.

However if you still want to include them with the `'extends'` option you can manually set the `'magicMethods'` option to `true`:

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

To enable **Monkey Patching** add the following `use` statement in the top of your specs:

```php
use kahlan\plugin\Monkey;
```

Monkey Patching allows replacement of core functions & classes which can't be stubbed like `time()`, `DateTime` or `MongoId` for example.

With Kahlan you can patch anything you want using `Monkey::patch()`.

For example I have the following class which needs to be patched:

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

Unbelivable right ? Moreover you can also replace the `time()` function by a simple closure:

```php
it("patches a core function with a closure", function() {

    $foo = new Foo();
    Monkey::patch('time', function(){return 123;});
    expect($foo->time())->toBe(123);

});
```

Using the same syntax, you can also patch any core classes by just monkey patching a fully-namespaced class name to another fully-namespaced class name.

You can find [another example on how to use Monkey Patching here](https://github.com/warrenseymour/kahlan-lightning-talk).

### <a name="monkey-patch-quit-statements"></a>Monkey Patch Quit Statements

When a unit test exercises code that contains an `exit()` or a `die()` statement, the execution of the whole test suite is aborted. With Kahlan, you can make all quit statements (i.e. like `exit()` or `die()`) to throw a `QuitException` instead of quitting the test suite for real.

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

    expect($closure)->toThrow(new QuitException('Exit statement occured', -1));
});
```

**Note:** This only work **for classes loaded by Composer**. If you try to create a stub with a `exit()` statement inside a closure it won't get intercepted by patchers and the application will quit for real. Indeed, **code in `*Spec.php` files are not intercepted & patched**.

## <a name="reporters"></a>7 - Reporters

Kahlan provide a flexible reporter system which can be extended easily.

There's two build-in reporters and the default is the dotted one:

```php
./bin/kahlan --reporter=dot # Default value
```

To use a reporter which looks like more a progress bar use the following option:
```php
./bin/kahlan --reporter=bar
```

However you can easily roll you own if these reporters don't fit your needs.

For example if you want a console based reporter create a PHP class which extends `kahlan\reporter\Terminal`. The `Terminal` class offers some useful methods like `console()` for doing some echos on the terminal. But if you wanted to create some kind of JSON reporter extending from `kahlan\reporter\Reporter` would be enough.

Example of a custom console reporter:
```php
<?php
namespace my\namespace;

class MyReporter extends \kahlan\reporter\Terminal
{
    /**
     * Callback called before any specs processing.
     *
     * @param array $params The suite params array.
     */
    public function begin($params)
    {
        parent::begin($params);
    }

    /**
     * Callback called before a spec.
     */
    public function before()
    {
    }

    /**
     * Callback called after a spec.
     */
    public function after()
    {
    }

    /**
     * Callback called when a new spec file is processed.
     */
    public function progress()
    {
        $this->_current++;
    }

    /**
     * Callback called on successful spec.
     */
    public function pass($report)
    {
        $this->console('✓', "green");
    }

    /**
     * Callback called on failure.
     */
    public function fail($report)
    {
        $this->console('☠', "red");
        $this->console("\n");
        $this->_report($report);
    }

    /**
     * Callback called when an exception occur.
     */
    public function exception($report)
    {
        $this->console('☠', "magenta");
        $this->console("\n");
        $this->_report($report);
    }

    /**
     * Callback called on a skipped spec.
     */
    public function skip($report)
    {
        $this->console('-', "cyan");
    }

    /**
     * Callback called at the end of specs processing.
     */
    public function end($results)
    {
        $this->console("\n");
        $this->_summary($results);
        $this->_exclusive($results);
    }
}
?>
```

**Note:** `_report()` & `_summary()` are also two inherited methods. Their roles are to format errors & display a summary of passed tests respectively. So feel free to dig into the source code if you want some more specific output for that.

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

`$this->_start` is the timestamp in micro seconds of when the process has been started. If passed to reporter, it'll be able to display some accurate execution time.

**Note:** `'myconsole'` is an arbitrary name, can be anything.

Let's run it:
```php
./bin/kahlan --config=my-config.php
```
![custom_reporter](assets/custom_reporter.png)

A bit ugly but the check marks and the skulls are present.

## <a name="pro-tips"></a>8 - Pro Tips

### Use the `--ff` option

`--ff` is the fast fail option. If used, the test suite will be stopped as soon as a failing test occurs. You can also specify a number of "allowed" fails before stoping the process. For example:

```
./bin/kahlan --ff=3
```

will stop the process as soon as 3 specs `it` failed.

### Use `--coverage` option

Kahlan has some built-in code coverage exporter (e.g. Coveralls & Scrutinizer exporters) but it can also be used to generates some detailed code coverage report directy inside the console.

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

### Use the exclusive prefix `x`

When writing your tests sometimes you want to **only execute** the test(s) you are working on. For this, you can prefix your spec with an `x` like in the following example:

```php
describe("test exclusive mode", function() {

    it("will be ignored", function() {
    });

    it("will be ignored", function() {
    });

    xit("will be runned", function() {
    });
});
```

If you want to run a subset instead of a single test you can use `xdescribe` or `xcontext` instead.

**Tip:** combined with `--coverage=<string>` this is a powerful combo to see exactly what part of the code is covered for a subset of specs only.

**Warning:** Jasmine uses `x` for ignoring a test. In Kahlan if you want to ignore a test just comment it out.

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
$args->option('ff', 'default', 1);
$args->option('coverage', 'default', 3);
$args->option('coverage-scrutinizer', 'default', 'scrutinizer.xml');
$args->option('coverage-coveralls', 'default', 'coveralls.json');

// The logic to inlude into the workflow.
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

Above `'kahlan.coveralls'` is just a custom name and could be whatever as long as `Filter::register()` && `Filter::apply()` are consistent on the namings.

`$this` refer to the Kahlan instance so `$this->reporters()->get('coverage')` will give you the instance of the coverage reporter. This coverage reporter will contain all raw data which is passed to the `Coveralls` exporter to be formatter.

For more information about filters, take a look at [the documentation of the filter library](https://github.com/crysalead/filter).

The filterable entry points are the following:

* `'workflow`'           # The one to rule them all
  * `'namespaces`'       # Adds some namespaces not managed by composer (like `spec`)
  * `'patchers`'         # Adds patchers
  * `'interceptor`'      # Setups the autoloader interceptor
  * `'loadSpecs`'        # Loads specs
  * `'reporters`'        # Adds reporters
    * `'console'`        # Creates the console reporter
    * `'coverage'`       # Creates the coverage reporter
  * `'start`'            # Useful for registering some pre process tasks
  * `'run`'              # Runs the test suite
  * `'reporting`'        # Runs some additionnal reporting tasks
  * `'stop`'             # Useful for registering some post process tasks


[You can see more details about how the workflow works here](https://github.com/crysalead/kahlan/blob/master/src/cli/Kahlan.php) (start reading with the `run()` method).

### Optimizations

Kahlan acts like a wrapper. It intercepts loaded classes Just It Time (i.e during the autoloading step) and rewrites the source code on the fly to make it easily testable with PHP. That's why Monkey Patching or redefining a class's method can be done inside the testing environment without any PECL extensions like runkit, aop, etc.

Notice that this approach will make your code to be runned a bit slower than your orginal code. However you can optimize Kahlan's interceptor to only patch the namespaces you want:

For example, the following configuration will only limit the patching to a bunch of namespaces/classes:

```php
$this->args()->set('interceptor-include', [
    'myapp',
    'lithium',
    'li3_zendserver\data\Job',
    'AuthorizeNetCIM'
]);
```

Conversely you can also exclude some external dependencies to speed up performances if you don't intend to Monkey Patch/Stub some namespaces/classes:
```php
$this->args()->set('interceptor-exclude', [
    'Symfony',
    'Doctrine'
]);
```

Finally you can also disable all the code patchers if you prefer to deal with DI only and not interested by Kahlan's features:
```php
$this->args()->set('interceptor-include', []);
```
**Note:** You will still able to stub instances & classes created with `Stub::create()`/`Stub::classname()` anyway.
