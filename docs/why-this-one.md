## Why This One?

One of PHP's assumptions is that once you define a function/constant/class it stays defined forever. Although this assumption is not really problematic when you are building an application, things get a bit more complicated if you want your application to be easily testable.

**The main test frameworks for PHP are:**

* [PHPUnit](https://phpunit.de) _(which reaches [23.80% of code coverage as of PHPUnit 4.4](assets/phpunit_4.4_code_coverage.png))_
* [phpspec](http://phpspec.net)
* [atoum](http://docs.atoum.org)
* [SimpleTest](http://www.simpletest.org)
* [Enhance-PHP](https://github.com/Enhance-PHP/Enhance-PHP)
* etc.

Whilst these "old school frameworks" are considered fairly mature, they don't allow easy testing of hard coded references.

Furthermore, they don't use the `describe-it` syntax either; `describe-it` allows a clean organization of tests to simplify their maintenance (avoiding [this kind of organization](https://github.com/sebastianbergmann/phpunit/tree/master/tests/Regression), for example!). Moreover, the `describe-it` syntax makes tests more reader-friendly (even better than the [atoum fluent syntax organization](https://github.com/atoum/atoum/blob/master/tests/units/classes/asserters/dateInterval.php))

**So what about new test frameworks for PHP ?**

* [Peridot](https://github.com/peridot-php/peridot)
* [pho](https://github.com/danielstjules/pho)
* [Testify](https://github.com/marco-fiset/Testify.php)
* [pecs](https://github.com/noonat/pecs)
* [speciphy](https://github.com/speciphy/speciphy)
* [dspec](https://github.com/davedevelopment/dspec)
* [preview](https://github.com/v2e4lisp/preview)
* etc.

In the list above, although [Peridot](https://github.com/peridot-php/peridot) seems to be mature, it only provides the `describe-it` syntax. And all other frameworks seems to be some simple proof of concept of the `describe-it` philosophy.

So, Kahlan was created out of frustration with all existing testing frameworks in PHP. Instead of introducing some new philosophical concepts, tools, java practices or other nonsense, Kahlan focuses on simply providing an environment which allows you to **easily test your code, even with hard coded references**.

To achieve this goal, **Kahlan allows you to stub or monkey patch your code**, just like in Ruby or JavaScript, without any required PECL-extentions. This way, you don't need to put [DI everywhere just to be able to write tests](http://david.heinemeierhansson.com/2012/dependency-injection-is-not-a-virtue.html)!

Some projects like [AspectMock](https://github.com/Codeception/AspectMock) attempted to bring this kind of metaprogramming flexibility for PHPUnit, but Kahlan aims to gather all of these facilities into a full-featured framework boasting a `describe-it` syntax, a lightweight approach and a simple API.

### Main Features

* Simple API
* Fast Code Coverage metrics ([xdebug](http://xdebug.org) required)
* Handy stubbing system ([mockery](https://github.com/padraic/mockery) or [prophecy](https://github.com/phpspec/prophecy) are no longer needed)
* Set stubs on your class methods directly (i.e allows dynamic mocking)
* Ability to Monkey Patch your code (i.e. allows replacement of core functions/classes on the fly)
* Ability to set stub on core classes through a Layer patcher (useful to set subs on Phalcon core classes for example)
* Check called methods on your class/instances
* Built-in Reporters/Exporters (Terminal, Coveralls, Code Climate, Scrutinizer, Clover)
* Extensible, customizable workflow
* Small code base (~10 times smaller than PHPUnit)

All of these features work with the [Composer](https://getcomposer.org/) autoloader out of the box. If you have your own autoloader check the [integration section](integration.md).
