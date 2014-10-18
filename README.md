# Kahlan - The PHP Test Framework

[![Build Status](https://travis-ci.org/crysalead/kahlan.png?branch=master)](https://travis-ci.org/crysalead/kahlan) [![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/crysalead/kahlan/badges/quality-score.png?s=7d13f5fc63cc67dc995baa2d303fb5c93aab53cc)](https://scrutinizer-ci.com/g/crysalead/kahlan/) [![Code Coverage](https://scrutinizer-ci.com/g/crysalead/kahlan/badges/coverage.png?s=5af80e51db6c0879b1cd47d5dc4c0ff24c4e9cf2)](https://scrutinizer-ci.com/g/crysalead/kahlan/) [![Coverage Status](https://coveralls.io/repos/crysalead/kahlan/badge.png?branch=master)](https://coveralls.io/r/crysalead/kahlan?branch=master)

Kahlan is a BDD Framework for PHP 5.4+, a la RSpec/JSpec using a Jasmine style notation.

# Documentation

See the whole [documentation here](docs/README.md).

# Requirements

 * PHP 5.4+
 * Using an autoloader in your project (Composer for example)
 * Xdebug if you want to perform code coverage analysis.

# Screenshot

Example of output:
![Kahlan](docs/assets/kahlan.png)

# Installation

```
git clone git@github.com:crysalead/kahlan.git
cd kahlan
composer install
bin/kahlan              # to run specs or,
bin/kahlan --coverage=3 # to run specs with coverage info for namespaces, classes & methods (require xdebug)
```

# Special Note

If you think that the code coverage of this library is not enough to give it a go, keep in mind that PHPUnit is achieving [23.80% after more that 10 years of experience in tests](docs/assets/phpunit_4.4_code_coverage.png).
