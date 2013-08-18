# Kahlan - The PHP Test Framework

[![Build Status](https://travis-ci.org/crysalead/kahlan.png?branch=master)](https://travis-ci.org/crysalead/kahlan) [![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/crysalead/kahlan/badges/quality-score.png?s=7d13f5fc63cc67dc995baa2d303fb5c93aab53cc)](https://scrutinizer-ci.com/g/crysalead/kahlan/) [![Code Coverage](https://scrutinizer-ci.com/g/crysalead/kahlan/badges/coverage.png?s=5af80e51db6c0879b1cd47d5dc4c0ff24c4e9cf2)](https://scrutinizer-ci.com/g/crysalead/kahlan/)

Kahlan is a behavior-driven development (BDD) library for PHP 5.4+, a la RSpec/JSpec using a Jasmine style notation.

# Features

 * Custom Matchers
 * Stubing/Mocking
 * Monkey Patching
 * Reporters
 * Code Coverage
 * Clean organization
 * Small (~5k loc)

# Requirements

 * PHP 5.4+
 * Using an Autoloader (Composer for example)

# Installation

```
git clone git@github.com:crysalead/kahlan.git
cd kahlan
composer install
php kahlan --coverage=3 # to run tests with coverage info for namespaces, classes & methods
```

See the whole [documentation here](http://crysalead.github.io/kahlan/).
