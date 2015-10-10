## Stubs

* [Method Stubbing](#method-stubbing)
* [Instance Stubbing](#instance-stubbing)
* [Class Stubbing](#class-stubbing)
* [Custom Stubbing](#custom-stubbing)

To enable **Method Stubbing** add the following `use` statement in the top of your specs:

```php
use kahlan\plugin\Stub;
```

### <a name="method-stubbing"></a>Method Stubbing

`Stub::on()` can stub any existing methods on any class.

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

Class stubbing is useful when you need to stub instance's methods of a specific class. Let's take the following code as example:

```php
namespace controller;

use Exception;
use model\User;

class testController {

    public function testFunction()
    {
        $user = new User();
        $user->name  = 'Username';
        $user->email = 'username@example.com';

        if (!$user->save()) {
            throw new Exception('Something gone wrong');
        }
    }
}
```

To test that the above exception is correctly thrown when `$user->save()` is false, you can roll on :

```php
use controller\testController;
use Exception;

describe("testController", function() {

    describe("->testFunction()", function() {

        it("throws an exception when save fails", function() {

            // Note: the sub must provide all arguments required by the `User::save()` method.
            Stub::on('model\User')->method("save", function() {
                return false;
            });

            expect(function() {
                $controller = new testController();
                $controller->testFunction();
            })->toThrow(new Exception('Something gone wrong'));

        });

    });

});
```

### <a name="custom-stubbing"></a>Custom Stubbing

There are also a couple of options for creating some stubs which inherit a class, implement interfaces or use traits.

An example using `'extends'`:

```php
it("stubs an instance with a parent class", function() {

    $stub = Stub::create(['extends' => 'kahlan\util\Text']);
    expect(is_object($stub))->toBe(true);
    expect(get_parent_class($stub))->toBe('kahlan\util\Text');

});
```
**Tip:** If you extends from an abstract class, all missing methods will be automatically added to your stub.

**Note:** If the `'extends'` option is used, magic methods **won't be included**, so as to to avoid any conflict between your tested classes and the magic method behaviors.

However, if you still want to include magic methods with the `'extends'` option, you can manually set the `'magicMethods'` option to `true`:

```php
it("stubs an instance with a parent class and keeps magic methods", function() {

    $stub = Stub::create([
        'extends'      => 'kahlan\util\Text',
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


### <a name="layer-stubbing"></a>Stubbing via a layer

#### Using a `Stub` instance.

With user defined classes, you can apply stubs everywhere. However this stubbing technique has some limitation with PHP core classes. Let's take the following example as an illustration:

```php
it("can't stubs PHP core method", function() {

    $redis = Stub::create(['extends' => 'Redis']);
    Stub::on($redis)->method('connect')->andReturn('stubbed');
    expect($stub->connect('127.0.0.1'))->toBe('stubbed'); //It fails

});
```

In the above example, `Redis` is a built-in class. So in this case, all inherited methods are not real PHP methods but some built-in C methods. And it's not possible to change the behavior of built-in C methods.

So the alternative here is to override all parent methods using the `'layer'` option to be PHP methods. With The layer option set to `true`, all methods from the parent class will be overrided in PHP to call their parent method in C. So the following spec will now pass.

```php
it("stubs overrided PHP core method", function() {

    $redis = Stub::create(['extends' => 'Redis', 'layer' => true]);
    Stub::on($redis)->method('connect')->andReturn('stubbed');
    expect($stub->connect('127.0.0.1'))->toBe('stubbed'); //It passes

});
```
