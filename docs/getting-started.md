# Getting Started

## Requirements

- PHP 5.5+
- [phpdbg](http://php.net/manual/en/debugger-about.php) or [Xdebug](http://xdebug.org/) (only required for code coverage analysis)

## Installation

The recommended way to install Kahlan is with [Composer](http://getcomposer.org/) as a *development* dependency of your project.

```bash
composer require --dev crysalead/kahlan
```

Alternatively, you may manually add `"crysalead/kahlan": "~3.0"` to the `require-dev` dependencies within your `composer.json`.


## Running Specs

Once Kahlan is installed, you can run your tests (referred to as *specs*) with:

```bash
./bin/kahlan
```

For a full list of the options, see the [CLI Options](cli-options.md).

## Directory Structure
The recommended directory structure is to add a `spec` directory at the top level of your project. You may then place your *Spec* files within this directory. Spec files should have a `Spec` suffix. The `spec` directory should mirror the structure of your source code directory.

An example directory structure would be:

```
├── spec                       # The directory containing your specs
│   └── ClassASpec.php
│   └── subdir
│       └── ClassBSpec.php
├── src                        # The directory containing your source code
│   └── ClassA.php
│   └── subdir
│       └── ClassB.php
├── composer.json
└── README.md
```
