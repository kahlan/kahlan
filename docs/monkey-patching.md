## Monkey Patching

* [Monkey Patch Quit Statements](#monkey-patch-quit-statements)

To enable **Monkey Patching**, add the following `use` statement in the top of your specs:

```php
use Kahlan\Plugin\Monkey;
```

Monkey Patching allows replacement of core functions and classes that can't be stubbed, for example [time()](http://php.net/manual/en/function.time.php), [DateTime](http://php.net/manual/en/class.datetime.php) or [MongoId](http://php.net/manual/en/class.mongoid.php) for example.

With Kahlan, you can patch anything you want using `Monkey::patch()`!

**Note:** Most of the time you won't use this directly and will use the `toReceive` and `toBeCalled` matchers instead.

Let's take the following class as example:

```php
namespace Spec\MonkeyTest;

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

You can patch the `time()` function on the fly like so:

```php
namespace spec;

use Spec\MonkeyTest\Foo;

describe("Monkey patching", function() {

    it("patches a core function with a closure", function() {

        $foo = new Foo();
        Monkey::patch('time', function(){
            return 123;
        });
        expect($foo->time())->toBe(123);

    });

});
```

Which could have been written with the `toBeCalled()` matcher:

```php
it("patches a core function with a closure", function() {

    expect('time')->toBeCalled()->andReturn(123);

    $foo = new Foo();
    expect($foo->time())->toBe(123);

});
```

Using the same syntax, you can also patch any core classes by just monkey patching a fully-namespaced class name to another fully-namespaced class name.

You can find [another example of how to use Monkey Patching here](https://github.com/warrenseymour/kahlan-lightning-talk).

### <a name="monkey-patch-quit-statements"></a>Monkey Patch Quit Statements

When a unit test exercises code that contains an `exit()` or a `die()` statement, the execution of the whole test suite is aborted. With Kahlan, you can make all quit statements (i.e. like `exit()` or `die()`) throw a `QuitException` instead of quitting the test suite for real.

To enable **Monkey Patching on Quit Statements** add the following `use` statements in the top of your tests:

```php
use Kahlan\QuitException;
use Kahlan\Plugin\Quit;
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
