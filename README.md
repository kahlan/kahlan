# Kahlan - The PHP Test Framework

[![Build Status](https://travis-ci.org/crysalead/kahlan.png?branch=master)](https://travis-ci.org/crysalead/kahlan) [![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/crysalead/kahlan/badges/quality-score.png?s=7d13f5fc63cc67dc995baa2d303fb5c93aab53cc)](https://scrutinizer-ci.com/g/crysalead/kahlan/) [![Code Coverage](https://scrutinizer-ci.com/g/crysalead/kahlan/badges/coverage.png?s=5af80e51db6c0879b1cd47d5dc4c0ff24c4e9cf2)](https://scrutinizer-ci.com/g/crysalead/kahlan/) [![Coverage Status](https://coveralls.io/repos/crysalead/kahlan/badge.png?branch=master)](https://coveralls.io/r/crysalead/kahlan?branch=master) [![Bitdeli Badge](https://d2weczhvl823v0.cloudfront.net/crysalead/kahlan/trend.png)](https://bitdeli.com/free "Bitdeli Badge")

Kahlan is a BDD Framework for PHP 5.4+, a la RSpec/JSpec using a Jasmine style notation.

# Features

 * Stubbing __real classes__
 * Monkey Patch __core classes and functions__
 * Inspect called methods __on real flow__
 * Code Coverage metrics
 * Add you custom Matchers/Reporters like a breeze
 * Clean specs organization
 * Small (~5k loc)

# Requirements

 * PHP 5.4+
 * Using an autoloader in your project (Composer for example)
 * Xdebug to perform code coverage analysis.

# How does it work?

Kahlan acts like a wrapper. It intercepts classes during the autoloading step and rewrites the source code on the fly to make it easily testable with PHP. That's why Monkey Patching or redefining a class's method can be done inside the testing environment without any PECL extensions like runkit, aop, etc.

Notice that all this processing produces some code which will be up to 3x slower than the orginal code. So it's strongly recommended to limit the use of such "rewriting" for testing and development environments where the execution time is not the priority.

# Installation

```
git clone git@github.com:crysalead/kahlan.git
cd kahlan
composer install
bin/kahlan              # to run specs or,
bin/kahlan --coverage=3 # to run specs with coverage info for namespaces, classes & methods (require xdebug)
```

See the whole [documentation here](http://crysalead.github.io/kahlan/).
