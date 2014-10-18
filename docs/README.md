# Kahlan - The PHP Test Framework
— the Freedom, Truth, and Justice **in one page** —

* [1 - Why Using It ?](#why-using-it)
* [2 - Getting Started](#getting-started)
* [3 - Overview](#overview)
* [4 - Matchers](#matchers)
    * [Classic matchers](#classic)
    * [Method invocation matchers](#method)
    * [Argument matchers](#argument)
    * [Custom matchers](#custom)
* [5 - Stubs](#stubs)
* [6 - Monkey Patching](#monkey-patching)
* [7 - Reporters](#reporters)
* [8 - Pro Tips](#pro-tips)

## <a name="why-using-it"></a>1 - Why Using It ?

You know that writing tests is the way to go, all the arguments make sense. But once the initial rush has passed the troubles start to apprear. Your tests are overcomplicated and need complete rewrite at each new code refactoring.

Don't worry, nobody seems performing very well in this domain myself included. Writing good tests requires to be as resilient as possible to refactoring and changes. So it's like playing chess, you need to anticipate your next moves to be in a "least worst" position after your next code refactoring/changes.

```
"Debugging is twice as hard as writing the code in the first place.
Therefore, if you write the code as cleverly as possible, you are,
by definition, not smart enough to debug it."
-- Brian Kernighan --
```

I found this definition pretty commonsensical here. Indeed if your tests are over complicated at the first place, it's like playing chess with hundred of pieces. And your ability to get out of it will only depends on your cleverness.

However tooling can be of a great help here. And there's a lot of awesome testing libraries arround like Jasmin, Mocha, Rspec, etc. which help you to keep your tests simple & organized.

So Kahlan was created out of frustration with all existing PHP testing frameworks. Instead of introducing some new philosophical concepts, tools, java practices, crap, Kahlan just provide an environment which allow you to easily test your code even wich hard coded references.

To achieve this goal Kahlan allow to stub or monkey patch your code directly like in Ruby or JavaScript without any required PECL-extentions. That way you won't need to put some [DI everywhere just for being able to write a test](http://david.heinemeierhansson.com/2012/dependency-injection-is-not-a-virtue.html).

### Main Features

* Small API & Small code base (~5k loc)
* Code Coverage metrics (xdebug will be required)
* Built-in Reporters/Exporters (Text, Coveralls, Scrutinizers)
* Easily extensible & customizable

If you are using the [Composer](https://getcomposer.org/) autoloader in your project you will be able to:
 * Set stubs on your class methods directly (also work for static methods).
 * Do some Monkey Patching (ie. allows replacement of core functions/classes on the fly).
 * Check called methods on your class/instances.

PS: If you want to use you custom autoloader you will need to create your own patcher for it.

## <a name="getting-started"></a>2 - Getting started

**Requirement: Just before continuing, make sure you have installed [Composer](https://getcomposer.org/).**

To make a long story short let's take [the following repository](https://github.com/crysalead/string) as an example.

It's a simple string class in PHP which give you a better understanding on how to structure a project to be easily testable with Kahlan.

Bellow you can see the detailed version of the tree structure adopted for this project:

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

So to start playing with it you just need to:

```
git clone git@github.com:crysalead/string.git
cd string
composer install
```

And then you'll be able to run specs with:
```
./bin/kahlan --coverage=4
```

**Note:** the `--coverage=4` option is of course optionnal.

You should now be able to build you own project by following the structure above.

## <a name="overview"></a>3 - Overview

### Describe Your Specs

Because test's organization is one of the key point of keeping clean and maintainable tests, Kahlan allow to group tests syntaxically using a closure syntax.

```php
describe("toBe::match", function() {

	it("passes if true === true", function() {
		expect(true)->toBe(true);
	});

});
```

* `describe`: it generally contains all specs for a method. Using the class method's name is probably the better option for a clean description.
* `context`: it's used to group tests according to some use cases. Using "when" or "with" followed by the description of the use case is generally a good practice.
* `it`: it contain the code to test. Keep its corresponding description short and clear.

### Setup and Teardown

As the name implies the `beforeEach` function is called once before **each** spec contained in a `describe`.

```php
describe("Setup and Teardown", function() {

	beforeEach(function() {
		$this->foo = 1;
	});

	describe("Setup and Teardown", function() {

		beforeEach(function() {
			$this->foo++;
		});

		it("expects that the foo variable is equal to 2", function() {
			expect($this->foo)->toBe(2);
		});

	});

});
```

Kahlan allow the following "Setup and Teardown" functions at each `describe/context` level:

* `before`: Runned once inside a describe or context before all its specs.
* `beforeEach`: Runned before each specs of the same level.
* `afterEach`: Runned after each specs of the same level.
* `after`: Runned once inside a describe or context after all its specs.

### Expectations

Expectations are built using the `expect` function which takes a value, called the **actual** which is chained with a matcher function taking the **expected** value as parameter.

```php
describe("Expectations", function() {

	it("expects that 5 > 4", function() {
		expect(5)->toBeGreaterThan(4);
	});

});
```

You can find [all built-in matchers here](#matchers).

### Negative Expectations

Any matcher can be evaluated negatively by chaining `expect` with `not` before calling the matcher.

```php
describe("Negative Expectations", function() {

	it("doesn't expect that 4 > 5", function() {
		expect(4)->not->toBeGreaterThan(5);
	});

});
```

### Variable scope

You can use `$this` for making a variable **available** for a sub scope.

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

#### Scope isolation

Note: A variable setted with `$this` inside a `describe/context` or a `it` will **not** be available in a parent scope.

```php
describe("Scope inheritance bis", function() {

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

You can use these methods inside any spec.

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
	expect(0)->toBeTruthy();
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

To enable **Argument Matching** just add the following use statement in the top of your tests:

```php
use kahlan\Arg;
```

With the `Arg` class you can use any classic matchers to test arguments.

```php
it("expects params match the argument matchers", function() {
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

You can create you own matchers. A matcher is a simple class with a static `match()` & `description()` methods.

Example of an `toBeZero()` matcher:

```php
namespace my\namespace;

class ToBeZero {

	public static function match($actual, $expected = null) {
		return $actual === 0;
	}

	public static function description() {
		return "be equal to 0.";
	}
}
```

Once created you only need to regiter it using the following syntax:

```php
kahlan\Matcher::register('toBeZero', 'my\namespace\ToBeZero');
```

## <a name="stubs"></a>5 - Stubs

To enable **Method Stubbing** add the following use statement in the top of your tests:

```php
use kahlan\plugin\Stub;
```

### Method Stubbing

`Subs::on()` can stub any existing methods on any class (and also unexisting methods if `__call()` and or `__callStatic()` are defined in your class).

```php
it("stubs a method", function() {
	$instance = new MyClass();
	Stub::on($instance)->method('myMethod')->andReturn('Good Morning World!');
	expect($instance->myMethod())->toBe('Good Morning World!');
});
```

You can also stub static methods:

```php
it("stubs a static method", function() {
	$instance = new MyClass();
	Stub::on($instance)->method('::myMethod')->andReturn('Good Morning World!');
	expect($instance::myMethod())->toBe('Good Morning World!');
});
```

You can use also use an array based syntax for reducing verbosity:

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

Or a closure:

```php
it("stubs a method using a closure", function() {
	Stub::on($foo)->method('message', function($param) { return $param; });
});
```

### Instance Stubbing

When you are testing your application, sometimes you need a simple polyvalent instance to simply receive a couple of calls for unit testing a behavior. In this case your can create a simple polyvalent instance using `Stub::create()`:

```php
it("generates a polyvalent instance", function() {
	$stub = Stub::create();
	expect(is_object($stub))->toBe(true);
});
```

### Class Stubbing

You can also create some specific class using `Stub::classname()`:

```php
it("generates a polyvalent class", function() {
	$stub = Stub::classname();
	expect(is_string($stub))->toBe(true);
});
```

Generated stubs implement by default `__call()`, `__callStatic()`,`__get()`, `__set()` and some other magic methods so you should be able to use it for any kind of instance/class substitution.

### Custom Stubs

You can also create some stub which inherits some classes, implements interfaces or uses traits.

```php
it("stubs an instance with a parent class", function() {
    $stub = Stub::create(['extends' => 'string\String']);
    expect(is_object($stub))->toBe(true);
    expect(get_parent_class($stub))->toBe('string\String');
});
```
```php
it("stubs an instance implementing some interface", function() {
    $stub = Stub::create(['implements' => ['ArrayAccess', 'Iterator']]);
    $interfaces = class_implements($stub);
    expect($interfaces)->toHaveLength(3);
    expect(isset($interfaces['ArrayAccess']))->toBe(true);
    expect(isset($interfaces['Iterator']))->toBe(true);
    expect(isset($interfaces['Traversable']))->toBe(true); //Comes with `'Iterator'`
});
```
```php
it("stubs an instance using a trait", function() {
    $stub = Stub::create(['uses' => 'spec\mock\plugin\stub\HelloTrait']);
    expect($stub->hello())->toBe('Hello World From Trait!');
});
```

## <a name="monkey-patching"></a>6 - Monkey Patching

To enable **Monkey Patching** add the following use statement in the top of your tests:

```php
use kahlan\plugin\Monkey;
```

Monkey Patching allows replacement of core functions/classes which can't be stubbed like `time()`, `DateTime` or `MongoId` for example.

With kahlan you can patch anything using `Monkey::patch()`.

For example I have the following class which need to be patched:

```php
namespace kahlan\monkey;

use DateTime;

class Foo {

	public function time() {
		return time();
	}

	public function datetime($datetime = 'now') {
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

describe("Monkey::patch", function() {
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

Using the same syntax, you can also patch any core classes or PHP classes by just patching a fully namepaced classname to another fully namepaced classname.

You can find [another example on how to use Monkey Patching here](https://github.com/warrenseymour/kahlan-lightning-talk).

### Monkey Patch Quit Statements

When a unit test exercises code that contains an `exit()` or `die()` statement, the execution of the whole test suite is aborted.

To enable **Monkey Patching on Quit Statements** add the following use statements in the top of your tests:

```php
use kahlan\QuitException;
use kahlan\plugin\Quit;
```

Then in your spec you can make all quit statements (i.e. `exit()` or `die()`) to throw a `QuitException` instead by using `Quit::disable()`:
```php
it("throws an exception when an exit statement occurs if not allowed", function() {
    Quit::disable();

    $closure = function() {
        $foo = new Foo();
        $foo->exitStatement(-1);  // This code will run something like:
                                     `function($status) {exit($status);}`
    };

    expect($closure)->toThrow(new QuitException('Exit statement occured', -1));
});
```

**Note:** This only work for classes loaded by Composer. If you try to create a stub with a `exitStatement()` method defined by a closure containing an `exit()` statement, it won't get intercepted by patchers so your application will quit for real. In other words, code in `*Spec.php` files are not patched.

## <a name="reporters"></a>7 - Reporters

Kahlan provide a flexible reporter system which can be extended easily.

By default there's two build-in reporters. The default is the dotted one:

```php
./bin/kahlan --reporter=dot
```

And the other looks like more a progress bar:
```php
./bin/kahlan --reporter=bar
```

However you can easily roll you own if thess reporters doesn't fit your needs.

First you'll need to create your custom reporter class. Since I want a console based reporter I'll just create a PHP class which extends `kahlan\reporter\Terminal` otherwise if I wanted to create some kind of JSON reporter I would use `kahlan\reporter\Reporter`. The `Terminal` just offers some useful method like the `console()` method for doing some echos on the terminal.

Example of custome reporter:
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

The next step is to register your new reporter so you'll need to create you own custom config file ([see pro tips for more informations about the config file](#pro-tips)).

Example of config file:
```php
<?php
use filter\Filter;
use my\namespace\reporter\MyReporter;

Filter::register('kahlan.my_reporting', function($chain) {
	$reporters = $this->reporters();
	$reporters->add('myconsole', new MyReporter(['start' => $this->_start));
});

Filter::apply($this, 'consoleReporter', 'kahlan.my_reporting');
?>
```

`$this->_start` is the timestamp in micro seconds of when the process has been started. It's passed to reporter in case of the execution time needs to be displayed by this reporter.

**Note:** `'myconsole'` is an arbitrary name, can be anything.

Let's run it:
```php
./bin/kahlan --config=my-config.php
```
![custom_reporter](assets/custom_reporter.png)

A bit ubgly but the check marks and the skulls are present.

## <a name="pro-tips"></a>8 - Pro Tips

### Use the --ff option

`--ff` is the fast fail option. If used, the test suite will be stopped as soon as a failing test occurs. You can also specify a number of "allowed" fails before stoping the process. For example:

```
./bin/kahlan --ff=3
```

will stop the process as soon as 3 tests failed.

### Use the exclusive prefix `x`

When writing your tests sometimes you want to only exectute the test(s) you are working on. For this, you can prefix your spec with an `x` like in the following example:

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

Ps: warning, Jasmine which use `x` for ingoring a test. In Kahlan if you want to ignore a test just comment it out.

## The Kahlan config file

If you want to set some default options, change the execution workflow or load some custom plugins at a boostrap level, you will need to setup you own config file.

Kahlan try first to load the `kahlan-config.php` file from the current directory out of the box, but you can define your own path using the `--config=myconfigfile.php` option in the command line. Custom `--config` can be useful if you want to set some specific configuration for Travis or something else.

Example of a config file:

```php

use filter\Filter;
use kahlan\reporter\coverage\exporter\Coveralls;

// Below we are setting some defaults options (Note: the ones in the command line will overwrite the ones below)
$this->args('ff', 1);
$this->args('coverage', 3);
$this->args('coverage-scrutinizer', 'scrutinizer.xml');
$this->args('coverage-coveralls', 'coveralls.json');

// Change the Kahlan workflow to add a job at a `postProcess` level.
Filter::register('kahlan.coveralls_reporting', function($chain) {
	$coverage = $this->reporters()->get('coverage');
	if (!$coverage || !$this->args('coverage-coveralls')) {
		return $chain->next();
	}
	Coveralls::write([
		'coverage' => $coverage,
		'file' => $this->args('coverage-coveralls'),
		'service_name' => 'travis-ci',
		'service_job_id' => getenv('TRAVIS_JOB_ID') ?: null
	]);
	return $chain->next();
});

Filter::apply($this, 'postProcess', 'kahlan.coveralls_reporting');
```

Above `'kahlan.coveralls_reporting'` is just a custom name and could be whatever as long as `Filter::register()` && `Filter::apply()` are consistent with the name.

`$this` is the Kahlan instance so $this->reporters()->get('coverage') will give you the instance of the coverage reporter. This coverage reporter contain all raw data which will be formatter for the coveralls service using the `Coveralls` exporter.

For more information about filters, take a look at [the documentation of the filter library](https://github.com/crysalead/filter).

The filterable entry points are the following:

* `'customNamespaces`'
* `'initPatchers`'
* `'patchAutoloader`'
* `'loadSpecs`'
* `'initReporters`'
  * `'consoleReporter'`  # Useful for initializing your custom reporter
  * `'coverageReporter'` # Useful for initializing your custom coverage reporter
* `'preProcess`'         # Useful for registering your custom matchers
* `'runSpecs`'
* `'postProcess`'        # Useful for some further process
* `'stop`'
* `'run`'                # The one to rule them all

[You can see more details about how the workflow works here](https://github.com/crysalead/kahlan/blob/master/src/cli/Kahlan.php) (start reading with the `run()` method).

### Optimizations

Kahlan acts like a wrapper. It intercepts loaded classes Just It Time (i.e during the autoloading step) and rewrites the source code on the fly to make it easily testable with PHP. That's why Monkey Patching or redefining a class's method can be done inside the testing environment without any PECL extensions like runkit, aop, etc.

Notice that this approach will make your code to be runned a bit slower than your orginal code. So if you are faced some performance issues with your previous framework, you can make Kahlan's interceptor to only patch some defined namespaces:

The following example will only limit patching to a bunch of namespaces/classes:

```php
$this->args('interceptor-include', [
    'myapp',
    'lithium',
    'li3_zendserver\data\Job',
    'AuthorizeNetCIM'
]);
```

Conversely you can also exclude some external dependencies to speed up performances if you don't intend to Monkey Patch/Stub some namespaces/classes:
```php
$this->args('interceptor-exclude', [
    'Symfony',
    'Doctrine'
]);
```

You can also remove all the patchers if you prefer to deal with DI only and are not interested to Monkey Patching:
```php
$this->args('interceptor-include', []);
```
**Note:** You will still able stub instances/classes created with `Stub::create()`/`Stub::classname()` anyway.
