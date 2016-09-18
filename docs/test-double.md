## Test Double

First add the following `use` statement in the top of your specs to be able to create test doubles:

```php
use Kahlan\Plugin\Double;
```

<a name="instance-double"></a>
### Instance Double

When you are testing an application, sometimes you need a simple, polyvalent instance for receiving a couple of calls. `Double::instance()` can create such polyvalent instance:

```php
it("makes a instance double", function() {
    $double = Double::instance();

    expect(is_object($double))->toBe(true);
    expect($double->something())->toBe(null);
});
```

There are also a couple of options for creating some stubs which inherit a class, implement interfaces or use traits.

Examples using `'extends'`:

```php
it("makes a instance double with a parent class", function() {
    $double = Double::instance(['extends' => 'Kahlan\Util\Text']);

    expect(is_object($double))->toBe(true);
    expect(get_parent_class($double))->toBe('Kahlan\Util\Text');
});
```
**Tip:** If you extend an abstract class, all missing methods will be automatically added to your stub.

**Note:** If the `'extends'` option is used, magic methods **won't be included**, so as to avoid any conflict between your tested classes and the magic methods.

However, if you still want to include magic methods with the `'extends'` option, you can manually set the `'magicMethods'` option to `true`:

```php
it("makes a instance double with a parent class and keeps magic methods", function() {
    $double = Double::instance([
        'extends'      => 'Kahlan\Util\Text',
        'magicMethods' => true
    ]);

    expect($double)->toReceive('__get')->ordered;
    expect($double)->toReceive('__set')->ordered;
    expect($double)->toReceive('__isset')->ordered;
    expect($double)->toReceive('__unset')->ordered;
    expect($double)->toReceive('__sleep')->ordered;
    expect($double)->toReceive('__toString')->ordered;
    expect($double)->toReceive('__invoke')->ordered;
    expect(get_class($double))->toReceive('__wakeup')->ordered;
    expect(get_class($double))->toReceiveNext('__clone')->ordered;

    $prop = $double->prop;
    $double->prop = $prop;
    isset($double->prop);
    unset($double->prop);
    $serialized = serialize($double);
    $string = (string) $double;
    $double();
    unserialize($serialized);
    $double2 = clone $double;
});
```

And it's also possible to extends built-in PHP classes.

```php
it("makes a instance double of a PHP core class", function() {
    $redis = Double::instance(['extends' => 'Redis']);
    allow($redis)->method('connect')->andReturn(true);

    expect($double->connect('127.0.0.1'))->toBe(true);
});
```

If you need your stub to implement a couple of interfaces you can use the `'implements'` option like so:

```php
it("makes a instance double implementing some interfaces", function() {
    $double = Double::instance(['implements' => ['ArrayAccess', 'Iterator']]);
    $interfaces = class_implements($double);

    expect($interfaces)->toHaveLength(3);
    expect(isset($interfaces['ArrayAccess']))->toBe(true);
    expect(isset($interfaces['Iterator']))->toBe(true);
    expect(isset($interfaces['Traversable']))->toBe(true); //Comes with `'Iterator'`
});
```

And if you need your stub to implement a couple of traits you can use the `'uses'` option like so:

```php
it("makes a instance double using a trait", function() {
    $double = Double::instance(['uses' => 'spec\mock\plugin\stub\HelloTrait']);

    expect($double->hello())->toBe('Hello World From Trait!');
});
```

**Note:** Generated stubs implements by default `__call()`, `__callStatic()`,`__get()`, `__set()` and some other magic methods for a maximum of polyvalence.

So `allow()` on stubs can be applied on any method name. Under the hood `__call()` will catch everything. You should pay attention that `method_exists` won't work on this "virtual method stubs". To make it works, you will need to add the necessary "endpoint(s)" using the `'methods'` option like in the following example:

```php
it("adds a custom endpoint", function() {
    $double = Double::instance(['methods' => ['myMethod']]);
    
    expect(method_exists($double, 'myMethod'))->toBe(true);
});
```

### <a name="class-double"></a>Class Double

You can also create class double names (i.e a string) using `Double::classname()`:

```php
it("makes a class double", function() {
    $class = Double::classname();
    expect(is_string($class))->toBe(true);

    $double = new $class()
    expect($double)->toBeAnInstanceOf($class);
});
```
