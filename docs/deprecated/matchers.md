## Matchers

* [Overview](#overview)
* [Classic matchers](#classic)
* [Method invocation matchers](#method)
* [Argument matchers](#argument)
* [Custom matchers](#custom)

### <a name="overview"></a>Overview

**Note:** Expectations can only be done inside `it` blocks.

Kahlan have a lot of matchers, that can help you up in your testing journey. All matchers can be chained up. It shown in a code below.

```php
it("can chain up a lots of matchers", function() {
   expect([1, 2, 3])->toBeA('array')->toBe([1, 2, 3])->toContain(1);
});
```

### <a name="classic"></a>Classic matchers

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

    expect([ 'a' =>1, 'b' => 2, 'c' => 3])->toContainKey(a);
    expect([ 'a' =>1, 'b' => 2, 'c' => 3])->toContainKey(a, b);
    expect([ 'a' =>1, 'b' => 2, 'c' => 3])->toContainKey([a, b]);

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

**Note:** You should **always remember** to use `toReceive`, `toReceiveNext` function **before** you call a method.

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

Once created you only need to [register it](config-file.md) using the following syntax:

```php
kahlan\Matcher::register('toBeZero', 'my\namespace\ToBeZero');
```

**Note:** custom matcher should be reserved to frequently used matching. For other cases, just use the `toMatch` matcher using the matcher closure as parameter.

It's also possible to register a matcher to work only for a specific class name to keep the API consistent.

Example:

```php
kahlan\Matcher::register('toContain', 'my\namespace\ToContain', ' SplObjectStorage');
```
