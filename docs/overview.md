## Overview

### Describe Your Specs

Because test organization is one of the key point of keeping clean and maintainable tests, Kahlan allow to group tests syntactically using a closure syntax.

```php
describe("ToBe", function() {

    describe("::match()", function() {

        it("passes if true === true", function() {

            expect(true)->toBe(true);

        });

    });

});
```

* `describe`: generally contains all specs for a method. Using the class method's name is probably the best option for a clean description.
* `context`: is used to group tests related to a specific use case. Using "when" or "with" followed by the description of the use case is generally a good practice.
* `it`: contains the code to test. Keep its description short and clear.

### Setup and Teardown

As the name implies, the `beforeEach` function is called once before **each** spec contained in a `describe`.

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

* `before`: Run once inside a `describe` or `context` before all contained specs.
* `beforeEach`: Run before each spec of the same level.
* `afterEach`: Run after each spec of the same level.
* `after`: Run once inside a `describe` or `context` after all contained specs.

### Expectations

Expectations are built using the `expect` function which takes a value, called the **actual**, as parameter and chained with a matcher function taking the **expected** value and some optional extra arguments as parameters.

```php
describe("Positive Expectation", function() {

    it("expects that 5 > 4", function() {

        expect(5)->toBeGreaterThan(4);

    });

});
```

You can find [all built-in matchers here](matchers.md).

### Negative Expectations

Any matcher can be evaluated negatively by chaining `expect` with `not` before calling the matcher:

```php
describe("Negative Expectation", function() {

    it("doesn't expect that 4 > 5", function() {

        expect(4)->not->toBeGreaterThan(5);

    });

});
```

### Asynchronous Expectations

To perform some asynchronous tests it's possible to use the `waitsFor` statement. This statement runs a passed closure until all contained expectations passes or a timeout is reached. `waitsFor` can be useful to waits for elements to appear/disappear on a browser page during some functionnal testing for example.

```php
describe("Asynchronous Expectations", function() {

    it("waits in vain", function() {

        waitsFor(function() {
            expect(false)->toBe(true);
        });

    });

    it("waits to be lucky", function() {

        waitsFor(function() {
            return mt_rand(0, 10);
        })->toBe(10);

    });

}, 10);
```

In the example above, the timeout has been setted globally at the bottom of `describe()` statement. However it can also be overrided at a `context()/it()` level or simply by setting the second parameter of `waitsFor()`. If no timeout is defined, the default timeout will be set to `0`.

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

**Note:** A variable assigned with `$this` inside either a `describe/context` or an `it` will **not** be available to a parent scope.

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

### Control-flow

Spec control flow is similar to `Jasmine`. In other words functions executed on a scope level using the following order `before`, `beforeEach`, `after` and `afterEach`.

```php
describe(function() {
    before(function() {
       //b1
    });
    describe(function() {
        before(function() {
           //b2
        });
        beforeEach(function() {
           //be1
        });
        it("runs a spec", function() {
           //it1
        });
        it("runs a spec", function() {
           //it2
        });
        afterEach(function() {
           //ae1
        });
        after(function() {
           //a2
        });
    });
    after(function() {
       //a1
    });
});
```

That code will give a following execution flow: `b1 - b2 - be1 - it1 - ae1 - be1 - it2 - ae1 - a2 - a1`
