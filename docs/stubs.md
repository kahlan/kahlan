## 5 - Stubs

* [Method Stubbing](#method-stubbing)
* [Instance Stubbing](#instance-stubbing)
* [Class Stubbing](#class-stubbing)
* [Custom Stubbing](#custom-stubbing)

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

### <a name="custom-stubbing"></a>Custom Stubbing

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
