# Kahlan

![Build Status](https://img.shields.io/badge/branch-master-blue.svg) [![Build Status](https://travis-ci.org/crysalead/kahlan.svg?branch=master)](https://travis-ci.org/crysalead/kahlan) [![HHVM Status](http://hhvm.h4cc.de/badge/crysalead/kahlan.svg)](http://hhvm.h4cc.de/package/crysalead/kahlan) [![License](https://poser.pugx.org/crysalead/kahlan/license.svg)](https://packagist.org/packages/crysalead/kahlan)

[![Latest Stable Version](https://poser.pugx.org/crysalead/kahlan/v/stable.svg)](https://packagist.org/packages/crysalead/kahlan)
[![Total Downloads](https://poser.pugx.org/crysalead/kahlan/downloads.svg)](https://packagist.org/packages/crysalead/kahlan)
[![Code Climate Coverage Status](https://codeclimate.com/github/crysalead/kahlan/badges/coverage.svg)](https://codeclimate.com/github/crysalead/kahlan)
[![Coveralls Coverage Status](https://coveralls.io/repos/crysalead/kahlan/badge.svg?branch=master)](https://coveralls.io/r/crysalead/kahlan?branch=master)
[![Scrutinizer Coverage Status](https://scrutinizer-ci.com/g/crysalead/kahlan/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/crysalead/kahlan/?branch=master)

Kahlan is a full-featured Unit & BDD test framework a la RSpec/JSpec which uses a `describe-it` syntax and moves testing in PHP one step forward.

Kahlan embraces the [KISS principle](http://en.wikipedia.org/wiki/KISS_principle) and makes Unit & BDD testing fun again!

**Killer feature:** Kahlan allows to stub or monkey patch your code directly like in Ruby or JavaScript without any required PECL-extentions.

## Video

* <a href="http://vimeo.com/116949820" target="_blank">Warren Seymour presentation at Unified Diff (2015)</a>

## IRC

**chat.freenode.net** (server)
**#kahlan** (channel)

## Documentation

See the whole [documentation here](http://kahlan.readthedocs.org/en/latest).

## Requirements

 * PHP 5.4+
 * Composer
 * [Xdebug](http://xdebug.org/) (if you want to perform code coverage analysis)

## Main Features

* Simple API
* Small code base (~10 times smaller than PHPUnit)
* Fast Code Coverage metrics ([xdebug](http://xdebug.org) required)
* Handy stubbing system ([mockery](https://github.com/padraic/mockery) or [prophecy](https://github.com/phpspec/prophecy) are no longer needed)
* Set stubs on your class methods directly (i.e allows dynamic mocking)
* Ability to Monkey Patch your code (i.e. allows replacement of core functions/classes on the fly)
* Check called methods on your class/instances
* Built-in Reporters/Exporters (Terminal, Coveralls, Code Climate, Scrutinizer, Clover)
* Extensible, customizable workflow

## Syntax

```php

describe("Example", function() {

    it("passes if true === true", function() {

        expect(true)->toBe(true);

    });

    it("passes if false !== true", function() {

        expect(false)->not->toBe(true);

    });

});

```

## Screenshots

Example of error reporting:
![Kahlan](docs/assets/kahlan.png)

Example of detailed code coverage on a specific scope:
![code_coverage_example](docs/assets/code_coverage_example.png)

## Installation

### via Composer

Here is a sample composer.json to install Kahlan:

```json
{
    "name": "example/kahlan",
    "description": "Demonstration of installing Kahlan through Composer",
    "require": {
    },
    "require-dev": {
        "crysalead/kahlan": "~1.1"
    },
    "license": "MIT"
}
```

Then install via:

```bash
composer install
```

Note:
Kahlan uses the [ferver](https://github.com/jonathanong/ferver) versioning so a `"~x.y"` version constraint shouldn't ever BC-break.

### via Git clone

```
git clone git@github.com:crysalead/kahlan.git
cd kahlan
composer install
bin/kahlan              # to run specs or,
bin/kahlan --coverage=4 # to run specs with coverage info for namespaces, classes & methods (require xdebug)
```
