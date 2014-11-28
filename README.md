# Kahlan

[![Build Status](https://travis-ci.org/crysalead/kahlan.png?branch=master)](https://travis-ci.org/crysalead/kahlan) [![Code Coverage](https://scrutinizer-ci.com/g/crysalead/kahlan/badges/coverage.png?s=5af80e51db6c0879b1cd47d5dc4c0ff24c4e9cf2)](https://scrutinizer-ci.com/g/crysalead/kahlan/) [![Coverage Status](https://coveralls.io/repos/crysalead/kahlan/badge.png?branch=master)](https://coveralls.io/r/crysalead/kahlan?branch=master)

Kahlan is a full-featured Unit & BDD test framework a la RSpec/JSpec which uses a `describe-it` syntax and moves testing in PHP one step forward.

Kahlan embraces the [KISS principle](http://en.wikipedia.org/wiki/KISS_principle) and makes Unit & BDD testing fun again!

**Killer feature:** Kahlan allows to stub or monkey patch your code directly like in Ruby or JavaScript without any required PECL-extentions.

# Documentation

See the whole [documentation here](docs/README.md).

# Requirements

 * PHP 5.4+
 * Composer
 * [Xdebug](http://xdebug.org/) (if you want to perform code coverage analysis)

# Screenshots

Example of output:
![Kahlan](docs/assets/kahlan.png)

Example of detailed code coverage on a specific scope:
![code_coverage_example](docs/assets/code_coverage_example.png)

# Installation

## via Composer

Here is a sample composer.json to install Kahlan:

```json
{
    "name": "example/kahlan",
    "description": "Demonstration of installing Kahlan through Composer",
    "require": {
    },
    "require-dev": {
        "crysalead/kahlan": "dev-master"
    },
    "license": "MIT",
    "minimum-stability": "dev"
}
```

Then install via:

```bash
composer install --dev
```

## via Git clone

```
git clone git@github.com:crysalead/kahlan.git
cd kahlan
composer install
bin/kahlan              # to run specs or,
bin/kahlan --coverage=4 # to run specs with coverage info for namespaces, classes & methods (require xdebug)
```
