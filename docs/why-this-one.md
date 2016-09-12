## Why This One?

One of PHP's assumptions is that once you define a function/constant/class it stays defined forever. Although this assumption is not really problematic when you are building an application, things get a bit more complicated if you want your application to be easily testable.

**The main test frameworks for PHP are:**

* [PHPUnit](https://phpunit.de) _(with [40% of code coverage as of PHPUnit 5.7](assets/phpunit_5.7_code_coverage.png))_
* [phpspec](http://phpspec.net) _(with [63% of code coverage as of phpspec 3.1](assets/phpspec_3.1_code_coverage.png))_
* [atoum](http://docs.atoum.org) _(with [84% of code coverage as of Atoum 2.8.2](https://coveralls.io/builds/7422587))_
* etc.

Whilst these "old school frameworks" are considered fairly mature, they are tied up to an inappropriate DSL (Domain Specific Langage) for writing tests.
Instead of using some `describe-it` syntax which allows a clean organization of tests to simplify their maintenance (and avoiding [this kind of organization](https://github.com/sebastianbergmann/phpunit/tree/master/tests/Regression), for example), they are still using class methods to write tests which is at the same time akward and inappropriate.

The other issue is that they don't support easy testing of hard coded references like in Ruby or Javascript because of some PHP limitations. A situation which lead to promote dependency injection everywhere [even when not needed](http://david.heinemeierhansson.com/2012/dependency-injection-is-not-a-virtue.html).

**What about new test frameworks for PHP ?**

* [Peridot](https://github.com/peridot-php/peridot)
* [pho](https://github.com/danielstjules/pho)
* [Testify](https://github.com/marco-fiset/Testify.php)
* [pecs](https://github.com/noonat/pecs)
* [speciphy](https://github.com/speciphy/speciphy)
* [dspec](https://github.com/davedevelopment/dspec)
* [preview](https://github.com/v2e4lisp/preview)
* etc.

In the list above, although [Peridot](https://github.com/peridot-php/peridot) seems to be mature, it only provides the `describe-it` syntax. And all other frameworks seems to be some simple proof of concept of the `describe-it` philosophy.

So, Kahlan was created out of frustration with all existing testing frameworks in PHP. Instead of introducing some new philosophical concepts, tools, java practices or other nonsense, Kahlan focuses on simply providing an environment which allows you to **easily test your code, even with hard coded references** using well-proven RSpec concepts.

So **Kahlan allows you to stub or monkey patch your code**, without any required PECL-extentions, and you won't need to put DI everywhere just to be able to write a test any more.

### Main Features

* RSpec/JSpec syntax
* Code Coverage metrics ([xdebug](http://xdebug.org) or [phpdbg](http://phpdbg.com/docs) required)
* Handy stubbing system ([mockery](https://github.com/padraic/mockery) or [prophecy](https://github.com/phpspec/prophecy) are no longer needed)
* Set stubs on your class methods directly (i.e allows dynamic mocking)
* Ability to Monkey Patch your code (i.e. allows replacement of core functions/classes on the fly)
* Check called methods on your classes/instances
* Built-in Reporters (Terminal or HTML reporting through [istanbul](https://gotwarlost.github.io/istanbul/) or [lcov](http://ltp.sourceforge.net/coverage/lcov.php))
* Built-in Exporters (Coveralls, Code Climate, Scrutinizer, Clover)
* Extensible, customizable workflow

All of these features work with the [Composer](https://getcomposer.org/) autoloader out of the box. If you have your own autoloader check the [integration section](integration.md).
