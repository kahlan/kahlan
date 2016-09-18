# Kahlan
> The Unit/BDD PHP Test Framework for Freedom, Truth, and Justice

Kahlan is a full-featured Unit & BDD test framework a la RSpec/JSpec which uses a `describe-it` syntax and moves testing in PHP one step forward.

**Kahlan allows to stub or monkey patch your code directly like in Ruby or JavaScript without any required PECL-extentions.**


<a name="features"></a>
### Features
- `describe-it` syntax similar to modern BDD testing frameworks
- Code Coverage metrics ([xdebug](http://xdebug.org) or [phpdbg](http://phpdbg.com/docs) required)
- Handy stubbing system ([mockery](https://github.com/padraic/mockery) or [prophecy](https://github.com/phpspec/prophecy) are no longer needed)
- Set stubs on your class methods directly (i.e allows dynamic mocking)
- Ability to Monkey Patch your code (i.e. allows replacement of core functions/classes on the fly)
- Check called methods on your classes/instances
- Built-in Reporters (Terminal or HTML reporting through [istanbul](https://gotwarlost.github.io/istanbul/) or [lcov](http://ltp.sourceforge.net/coverage/lcov.php))
- Built-in Exporters (Coveralls, Code Climate, Scrutinizer, Clover)
- Extensible, customizable workflow


<a name="license"></a>
## License
Licensed using the [MIT license](http://opensource.org/licenses/MIT).

> The MIT License (MIT)
>
> Copyright (c) 2014 CrysaLEAD
>
> Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
>
> The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
>
> THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.


<a name="contributing"></a>
## Contributing
To contribute to Kahlan, [open a pull request](https://help.github.com/articles/creating-a-pull-request/) against the `master` branch with your change. Be sure to update the specs to verify your change works as expected and to prevent regressions.


## Documentation
- [Overview](overview.md)
  - [Features](overview.md#features)
  - [License](overview.md#license)
  - [Contributing](overview.md#contributing)
- [Getting Started](getting-started.md)
  - [Requirements](getting-started.md#requirements)
  - [Installation](getting-started.md#installation)
  - [Running Kahlan](getting-started.md#running-kahlan)
  - [Directory Structure](getting-started.md#directory-structure)
- [DSL](dsl.md)
- [Matchers](matchers.md)
  - [Classic matchers](matchers.md#classic)
  - [Method invocation matchers](matchers.md#method)
  - [Argument matchers](matchers.md#argument)
  - [Custom matchers](matchers.md#custom)
- [Method Stubbing & Monkey Patching](allow.md)
    - [Replacing a method](allow.md#method-stubbing)
    - [Replacing a function](allow.md#function-stubbing)
    - [Replacing a class](allow.md#monkey-patching)
- [Test Double](test-double.md)
    - [Instance Double](test-double.md#instance-double)
    - [Class Double](test-double.md#class-double)
- [Quit Statement Patching](quit.md)
- [CLI Options](cli-options.md)
- [Reporters](reporters.md)
- [Pro Tips](pro-tips.md) - including CLI arguments
- [The `kahlan-config.php` file](config-file.md)
- [Integration with popular frameworks](integration.md)
