![Kahlan](docs/assets/logo.png "Kahlan")
<hr/>

![Build Status](https://img.shields.io/badge/branch-master-blue.svg) [![Build Status](https://travis-ci.org/crysalead/kahlan.svg?branch=master)](https://travis-ci.org/crysalead/kahlan) [![HHVM Status](http://hhvm.h4cc.de/badge/crysalead/kahlan.svg?style=flat)](http://hhvm.h4cc.de/package/crysalead/kahlan) [![License](https://poser.pugx.org/crysalead/kahlan/license.svg)](https://packagist.org/packages/crysalead/kahlan)

[![Latest Stable Version](https://poser.pugx.org/crysalead/kahlan/v/stable.svg)](https://packagist.org/packages/crysalead/kahlan)
[![Total Downloads](https://poser.pugx.org/crysalead/kahlan/downloads.svg)](https://packagist.org/packages/crysalead/kahlan)
[![Code Climate Coverage Status](https://codeclimate.com/github/crysalead/kahlan/badges/coverage.svg)](https://codeclimate.com/github/crysalead/kahlan)
[![Coveralls Coverage Status](https://coveralls.io/repos/crysalead/kahlan/badge.svg?branch=master)](https://coveralls.io/r/crysalead/kahlan?branch=master)
[![Scrutinizer Coverage Status](https://scrutinizer-ci.com/g/crysalead/kahlan/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/crysalead/kahlan/?branch=master)

Kahlan is a full-featured Unit & BDD test framework a la RSpec/JSpec which uses a `describe-it` syntax and moves testing in PHP one step forward.

**Kahlan allows to stub or monkey patch your code directly like in Ruby or JavaScript without any required PECL-extentions.**

## Videos

* <a href="http://vimeo.com/116949820" target="_blank">Warren Seymour presentation at Unified Diff (2015)</a>
* <a href="https://www.grafikart.fr/tutoriels/php/tdd-kahlan-805" target="_blank">Grafikart presentation in French (2016)</a>

## IRC

**chat.freenode.net** (server)
**#kahlan** (channel)

## Documentation

See the whole [documentation here](http://kahlan.readthedocs.org/en/latest)

## Requirements

 * PHP 5.5+
 * Composer
 * [phpdbg](http://php.net/manual/en/debugger-about.php) or [Xdebug](http://xdebug.org/) (required for code coverage analysis only)

## Main Features

* RSpec/JSpec syntax
* Code Coverage metrics ([xdebug](http://xdebug.org) or [phpdbg](http://phpdbg.com/docs) required)
* Handy stubbing system ([mockery](https://github.com/padraic/mockery) or [prophecy](https://github.com/phpspec/prophecy) are no longer needed)
* Set stubs on your class methods directly (i.e allows dynamic mocking)
* Ability to Monkey Patch your code (i.e. allows replacement of core functions/classes on the fly)
* Check called methods on your classes/instances
* Built-in Reporters (Terminal or HTML reporting through [istanbul](https://gotwarlost.github.io/istanbul/) or [lcov](http://ltp.sourceforge.net/coverage/lcov.php))
* Built-in Exporters (Coveralls, Code Climate, Scrutinizer, Clover)
* Extensible, customizable workflow

## Syntax

```php
<?php

describe("Example", function() {

    it("makes an expectation", function() {

         expect(true)->toBe(true);

    });

    it("expects methods to be called", function() {

        expect($user)->toReceive('save')->with(['validates' => false]);

        $user = new User();
        $user->validates(['validates' => false]);

    });

    it("stubs a function", function() {

        allow('time')->toBeCalled()->andReturn(123);
        $user = new User();
        expect($user->save())->toBe(true)
        expect($user->created)->toBe(123);

    });

    it("stubs a class", function() {

        allow('PDO')->toReceive('prepare', 'fetchAll')->andReturn([['name' => 'bob']]);
        $user = new User();
        expect($user->all())->toBe([['name' => 'bob']]);

    });

});

```

## Screenshots

### Example of default reporting:
![dot_reporter](docs/assets/dot_reporter.png)

### Example of verbose reporting:
![verbose_reporter](docs/assets/verbose_reporter.png)

### Example of code coverage on a specific scope:
![code_coverage](docs/assets/code_coverage.png)

## Installation

### via Composer

```bash
$ composer require --dev crysalead/kahlan
```

Note:
Kahlan uses the [ferver](https://github.com/jonathanong/ferver) versioning so a `"~x.y"` version constraint shouldn't ever BC-break.

### via Git clone

```
git clone git://github.com/crysalead/kahlan.git
cd kahlan
composer install
bin/kahlan              # to run specs or,
bin/kahlan --coverage=4 # to run specs with coverage info for namespaces, classes & methods (require xdebug)
```
