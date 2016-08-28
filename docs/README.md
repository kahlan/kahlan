# Kahlan
> The Unit/BDD PHP Test Framework for Freedom, Truth, and Justice

Kahlan is a full-featured Unit & BDD test framework a la RSpec/JSpec which uses a `describe-it` syntax and moves testing in PHP one step forward.

Kahlan embraces the [KISS principle](http://en.wikipedia.org/wiki/KISS_principle) and makes Unit & BDD testing fun again!

**Killer features:**
Kahlan is able to stub or monkey patch your code directly like in Ruby or JavaScript without any required PECL-extentions. You can stub classic or static methods of userland PHP code as well as internal PHP C-extenstions out of the box.

## Video

* <a href="http://vimeo.com/116949820" target="_blank">Warren Seymour presentation at Unified Diff (2015)</a>

## IRC

**chat.freenode.net** (server)
**#kahlan** (channel)

## Download

[Download Kahlan on Github](https://github.com/kahlan/kahlan)

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
* [Stubs](stubs.md)
  * [Method Stubbing](stubs.md#method-stubbing)
  * [Instance Stubbing](stubs.md#instance-stubbing)
  * [Class Stubbing](stubs.md#class-stubbing)
  * [Custom Stubbing](stubs.md#custom-stubbing)
* [Monkey Patching](monkey-patching.md)
  * [Monkey Patch Quit Statements](monkey-patching.md#monkey-patch-quit-statements)
* [Reporters](reporters.md)
* [Pro Tips](pro-tips.md) - including CLI arguments
* [The `kahlan-config.php` file](config-file.md)
* [Integration with popular frameworks](integration.md)
* [FAQ](faq.md)
