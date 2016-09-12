# Kahlan
> The Unit/BDD PHP Test Framework for Freedom, Truth, and Justice

Kahlan is a full-featured Unit & BDD test framework a la RSpec/JSpec which uses a `describe-it` syntax and moves testing in PHP one step forward.

**Kahlan allows to stub or monkey patch your code directly like in Ruby or JavaScript without any required PECL-extentions.**

## Videos

* <a href="http://vimeo.com/116949820" target="_blank">Warren Seymour presentation at Unified Diff (2015)</a>
* <a href="https://www.grafikart.fr/tutoriels/php/tdd-kahlan-805" target="_blank">Grafikart presentation in French (2016)</a>

## IRC

**chat.freenode.net** (server)
**#kahlan** (channel)

## Requirements

 * PHP 5.5+
 * Composer
 * [phpdbg](http://php.net/manual/en/debugger-about.php) or [Xdebug](http://xdebug.org/) (required for code coverage analysis only)

## Download

[Download Kahlan on Github](https://github.com/crysalead/kahlan)

## Installation though Composer
```
composer require --dev crysalead/kahlan
```

## Documentation

* [Why This One?](why-this-one.md)
* [Getting Started](getting-started.md)
* [CLI Options](cli-options.md)
* [Overview](overview.md)
* [Matchers](matchers.md)
  * [Classic matchers](matchers.md#classic)
  * [Method invocation matchers](matchers.md#method)
  * [Argument matchers](matchers.md#argument)
  * [Custom matchers](matchers.md#custom)
* [Method Stubbing & Monkey Patching](allow.md)
    * [Replacing a method](allow.md#method-stubbing)
    * [Replacing a function](allow.md#function-stubbing)
    * [Replacing a class](allow.md#monkey-patching)
* [Test Double](test-double.md)
    * [Instance Double](test-double.md#instance-double)
    * [Class Double](test-double.md#class-double)
* [Quit Statement Patching](quit.md)
* [Reporters](reporters.md)
* [Pro Tips](pro-tips.md) - including CLI arguments
* [The `kahlan-config.php` file](config-file.md)
* [Integration with popular frameworks](integration.md)
* [FAQ](faq.md)
