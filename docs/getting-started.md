## Getting started

**Requirement: Just before continuing, make sure you have installed [Composer](https://getcomposer.org/).**

To make a long story short let's take [the following repository](https://github.com/crysalead/text) as an example.

It's a simple string class in PHP which give you a better understanding on how to structure a project to be easily testable with Kahlan.

Here is the tree structure of this project:

```
├── bin
├── .gitignore
├── .scrutinizer.yml           # Optional, it's for using https://scrutinizer-ci.com
├── .travis.yml                # Optional, it's for using https://travis-ci.org
├── composer.json              # Need at least the Kahlan dependency
├── LICENSE.txt
├── README.md
├── spec                       # The directory which contain specs
│   └── text
│       └── TextSpec.php     # Name of spec should match pattern *Spec.php
├── src                        # The directory which contain sources code
│   └── Text.php
```

To start playing with it you'll need to:

```bash
git clone git://github.com/crysalead/text.git
cd text
composer install
```

And then run the tests (referred to as 'specs') with:

```bash
./bin/kahlan --coverage=4
```

**Note:** the `--coverage=4` option is optional.

PS: If your library is not compatible with composer, check the [integration section](integration.md).
