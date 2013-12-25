# Kahlan - The PHP Test Framework

[![Build Status](https://travis-ci.org/crysalead/kahlan.png?branch=master)](https://travis-ci.org/crysalead/kahlan) [![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/crysalead/kahlan/badges/quality-score.png?s=7d13f5fc63cc67dc995baa2d303fb5c93aab53cc)](https://scrutinizer-ci.com/g/crysalead/kahlan/) [![Code Coverage](https://scrutinizer-ci.com/g/crysalead/kahlan/badges/coverage.png?s=5af80e51db6c0879b1cd47d5dc4c0ff24c4e9cf2)](https://scrutinizer-ci.com/g/crysalead/kahlan/) [![Coverage Status](https://coveralls.io/repos/crysalead/kahlan/badge.png?branch=master)](https://coveralls.io/r/crysalead/kahlan?branch=master) [![Bitdeli Badge](https://d2weczhvl823v0.cloudfront.net/crysalead/kahlan/trend.png)](https://bitdeli.com/free "Bitdeli Badge")

Kahlan is a BDD Framework for PHP 5.4+, a la RSpec/JSpec using a Jasmine style notation.

# Features

 * Custom Matchers
 * Stubing __on real classes__.
 * Monkey Patching
 * Reporters
 * Code Coverage
 * Clean organization
 * Small (~5k loc)

# Requirements

 * PHP 5.4+
 * Using an Autoloader (Composer for example)
 * Xdebug to perform code coverage analysis.

# Installation

```
git clone git@github.com:crysalead/kahlan.git
cd kahlan
composer install
bin/kahlan              # to run tests or,
bin/kahlan --coverage=3 # to run tests with coverage info for namespaces, classes & methods (require xdebug)
```

See the whole [documentation here](http://crysalead.github.io/kahlan/).
