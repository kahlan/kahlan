### Quit Statement Patching

When a unit test exercises code that contains an `exit()` or a `die()` statement, the execution of the whole test suite is aborted. With Kahlan, you can make all quit statements (i.e. like `exit()` or `die()`) throw a `QuitException` instead of quitting the test suite for real.

To enable **Quit Statements Patching** add the following `use` statements in the top of your tests:

```php
use Kahlan\QuitException;
use Kahlan\Plugin\Quit;
```

And then use `Quit::disable()` like so:

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

**Note:** monkey patching only work **for classes loaded by Composer**. If you try to create a stub with a `exit()` statement inside a spec file it won't get intercepted by patchers. **All code in `*Spec.php` files are not intercepted and patched**.