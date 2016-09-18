## Stubs & Monkey Patching DSL

A method stub or simply stub in software development is used to stand in for some other programming functionality. This section explains how to perform such replacement with Kahlan.

### Method Stubbing

Use `allow()` to stub an existing method on any class like so:

```php
it("stubs a method by setting a return value", function() {
    $instance = new MyClass();
    allow($instance)->toReceive('myMethod')->andReturn('Good Morning World!');

    expect($instance->myMethod())->toBe('Good Morning World!');
});
```

```php
it("stubs a method by setting a return value only when some arguments matches", function() {
    $instance = new MyClass();
    allow($instance)->toReceive('myMethod')->with('Hello!')->andReturn('Good Morning World!');

    expect($instance->myMethod('Hello!'))->toBe('Good Morning World!');
    expect($instance->myMethod())->toBe(null);
});
```

You can specify multiple return values with:

```php
it("stubs a method with multiple return values", function() {
    $instance = new MyClass();
    allow($instance)->toReceive('sequential')->andReturn(1, 3, 2);

    expect($instance->sequential())->toBe(1);
    expect($instance->sequential())->toBe(3);
    expect($instance->sequential())->toBe(2);
});
```

You can also stub `static` methods using `::`:

```php
it("stubs a static method", function() {
    $instance = new MyClass();
    allow($instance)->toReceive('::myMethod')->andReturn('Good Morning World!');

    expect($instance::myMethod())->toBe('Good Morning World!');
});
```

It's also possible to use a closure to replace the whole method logic:

```php
it("stubs a method using a closure", function() {
    allow($foo)->toReceive('myMethod')->andRun(function($param) { return $param; });
    expect($instance->myMethod('Hello World!'))->toBe('Hello World!');
});
```

Moreover you can stub a chain of methods by using the following syntax.

```php
it('should patch PDO', function() {
    allow('PDO')->toReceive('prepare', 'fetchAll')->andReturn([['name' => 'bob']]);

    $user = new User();
    expect($user->all())->toBe([
        ['name' => 'bob']
    ]);
});
```

Where the `User` class is:

```php
<?php
use PDO;

class User
{
    protected $_db = null;

    public function __construct()
    {
        $this->_db = new PDO('mysql:dbname=testdb;host=localhost', 'root','');
    }

    public function all()
    {
        $stmt = $this->db->prepare('SELECT * FROM users');
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
```

In practice method chaining is considered as code smells because it tends to violate the Law of Demeter. So use it wisely.

Finally, `where()` can be used to specify some arguments requirement for a chain of methods:

```php
it('returns the stubbed return value when arguments requirement match', function() {
    $query = new MyQuery();
    allow($query)
      ->toReceive('find', 'where', 'order', 'limit')
      ->where([
        'find'  => ['widgets']],
        'where' => [['name' => 'Bottle Opener']],
        'order' => [['id' => 'desc']],
        'limit' => [10]
      ])
      ->andReturn([[id => '123','name' => 'Bottle Opener']]);

    expect($query->find('widgets')
        ->where(['name' => 'Bottle Opener'])
        ->order(['id' => 'desc'])
        ->limit(10))->toBe([[id => '123','name' => 'Bottle Opener']]);
});
```

### <a name="function-stubbing"></a>Function Stubbing

Use `allow()` to stub almost all functions like so:

```php
it("shows some examples of function stubbing", function() {
    allow('time')->toBeCalled()->andReturn(123);
    allow('time')->toBeCalled()->andReturn(123, 456, 789);
    allow('time')->toBeCalled()->andRun(function() { return 123; });

    allow('rand')->toBeCalled()->with(0, 10)->andReturn(5);
});
```

### <a name="monkey-patching"></a>Monkey Patching

Use `allow()` to monkey patch classes like so:

```php
it("shows some examples of function stubbing", function() {
    // Monkey patch `PDO` and stub chained methods under the hood.
    allow('PDO')->toReceive('prepare->fetchAll')->andReturn([['name' => 'bob']]);
    allow('PDO')->toReceive('prepare->fetchAll')->andRun(function() {
        return [['name' => 'bob']];
    });

    // Monkey patch `PDO` with a specific class.
    allow('PDO')->toBe('My\Alternative\PDO');

    // Monkey patch `DateTime` with a specific instance.
    allow('DateTime')->toBe(new DateTime('@123'));

    // Monkey patch `PDO` with a generic stub instance.
    allow('PDO')->toBeOK();
});
```
