## Matchers

**Note:** Expectations can only be done inside `it` blocks.

Kahlan has a lot of matchers that can help you in your testing journey. All matchers can be chained.

```php
it("can chain up a lots of matchers", function() {
   expect([1, 2, 3])->toBeA('array')->toBe([1, 2, 3])->toContain(1);
});
```
<a name="classic-matchers"></a>
### Classic matchers

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

**toBeA($expected)**

```php
it("passes if $actual is of a specific type", function() {
    expect('Hello World!')->toBeA('string');
    expect(false)->toBeA('boolean');
    expect(new stdClass())->toBeA('object');
});
```

*Supported types:*
* string
* integer
* float (floating point numbers - also called double)
* boolean
* array
* object
* null
* resource

**toBeAnInstanceOf($expected)**

```php
it("passes if $actual is an instance of stdObject", function() {
    expect(new stdClass())->toBeAnInstanceOf('stdObject');
});
```

**toHaveLength($expected)**

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

**toContainKey($expected)**

```php
it("passes if $actual contain $expected key(s)", function() {
    expect(['a' => 1, 'b' => 2, 'c' => 3])->toContainKey(a);
    expect(['a' => 1, 'b' => 2, 'c' => 3])->toContainKey(a, b);
    expect(['a' => 1, 'b' => 2, 'c' => 3])->toContainKey([a, b]);
});
```

**toBeCloseTo($expected, $precision)**

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

**toMatchEcho($expected)**

```php
it("passes if $closure echoes the expected regex output", function() {
    $closure = function() {
        echo "Hello World!";
    };

    expect($closure)->toMatchEcho('/^H(.*?)!$/');
});
```

```php
it("passes if $actual matches the $expected closure logic", function() {
    expect('Hello World!')->toMatchEcho(function($actual) {
        return $actual === 'Hello World!';
    });
});
```

### <a name="method"></a>Method invocation matchers

**Note:** You should **always remember** to use `toReceive` function **before** you call a method.

**toReceive($expected)**

```php
it("expects $foo to receive message() with the correct param", function() {
    $foo = new Foo();

    expect($foo)->toReceive('message')->with('My Message');
    expect($foo->message('My Message'))->toBe($foo);
});
```

```php
it("expects $foo to receive message() and bail out using a stub", function() {
    $foo = new Foo();

    expect($foo)->toReceive('message')->andReturn('something');
    expect($foo->message('My Message'))->toBe('something');
});
```

**Note:** When `andReturn()/andRun()` is not applied, `toReceive()` simply act as a "spy" and let the code execution flow to be unchanged. However when applied, the code execution will bail out with the stub value.

```php
it("expects $foo to receive message() and bail out using a closure for stub", function() {
    $foo = new Foo();

    expect($foo)->toReceive('message')->andRun(function() {
        return 'something';
    });
    expect($foo->message('My Message'))->toBe('something');
});
```

```php
it("expects Foo to receive ::message() with the correct param", function() {
    expect(Foo::class)->toReceive('::message')->with('My Message');
    Foo::message('My Message');
});
```

```php
it("expects Foo to receive ::message() with the correct param only once", function() {
    expect(Foo::class)->toReceive('::message')->with('My Message')->once();
    Foo::message('My Message');
});
```

```php
it("expects Foo to receive ::message() with the correct param a specified number of times", function() {
    expect(Foo::class)->toReceive('::message')->with('My Message')->time(2);
    $foo::message('My Message');
    $foo::message('My Message');
});
```

```php
it("expects $foo to receive message() followed by foo()", function() {
    $foo = new Foo();
    expect($foo)->toReceive('message')->ordered;
    expect($foo)->toReceive('foo')->ordered;
    $foo->message();
    $foo->foo();
});
```

```php
it("expects $foo to receive message() but not followed by foo()", function() {
    $foo = new Foo();
    expect($foo)->toReceive('message')->ordered;
    expect($foo)->not->toReceive('foo')->ordered;
    $foo->foo();
    $foo->message();
});
```

**Note:** You should pay attention that using such matchers will make your tests more "fragile" and can be identified as code smells even though not all code smells indicate real problems.

### <a name="method"></a>Function invocation matchers

**Note:** You should **always remember** to use `toBeCalled` function **before** you call a method.

**toBeCalled()**

```php
it("expects `time()` to be called", function() {
    $foo = new Foo();
    expect('time')->toBeCalled();
    $foo->date();
});
```

**Note:** When `andReturn()/andRun()` is not applied, `toBeCalled()` simply act as a "spy" and let the code execution flow to be unchanged. However when applied, the code execution will bail out with the stub value.

```php
it("expects `time()` to be called", function() {
    $foo = new Foo();
    expect('time')->toBeCalled()->andReturn(strtotime("now"));
    $foo->date();
});
```

```php
it("expects `time()` to be called", function() {
    $foo = new Foo();

    expect('time')->toBeCalled()->andRun(function() {
        return strtotime("now")
    });

    $foo->date();
});
```

```php
it("expects `time()` to be called with the correct param only once", function() {
    $foo = new Foo();
    expect('time')->toBeCalled()->with()->once();
    $foo->date();
});
```

```php
it("expects `time()` to be called and followed by `rand()`", function() {
    $foo = new Foo();
    expect('time')->toBeCalled()->ordered;
    expect('rand')->toBeCalled()->ordered;
    $foo->date();
    $foo->random();
});
```

```php
it("expects `time()` to be called and followed by `rand()`", function() {
    $foo = new Foo();
    expect('time')->toBeCalled()->ordered;
    expect('rand')->toBeCalled()->ordered;
    $foo->random();
    $foo->date();
});
```

### <a name="argument"></a>Argument Matchers

To enable **Argument Matching** add the following `use` statement in the top of your tests:

```php
use Kahlan\Arg;
```

With the `Arg` class you can use any existing matchers to test arguments.

```php
it("expects args to match the argument matchers", function() {
    $foo = new Foo();
    expect($foo)->toReceive('message')->with(Arg::toBeA('boolean'))->ordered;
    expect($foo)->toReceive('message')->with(Arg::toBeA('string'))->ordered;
    $foo->message(true);
    $foo->message('Hello World!');
});
```

```php
it("expects args match the toContain argument matcher", function() {
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

Once created you only need to [register it](config-file.md) using the following syntax:

```php
Kahlan\Matcher::register('toBeZero', 'my\namespace\ToBeZero');
```

**Note:** custom matcher should be reserved to frequently used matching. For other cases, just use the `toMatch` matcher using the matcher closure as parameter.

It's also possible to register a matcher to work only for a specific class name to keep the API consistent.

Example:

```php
Kahlan\Matcher::register('toContain', 'my\namespace\ToContain', ' SplObjectStorage');
```
