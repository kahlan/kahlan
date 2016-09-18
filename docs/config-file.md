## The `kahlan-config.php` file

If you want to set some default options, change the execution workflow or load some custom plugins at a bootstrap level, you will need to setup you own config file.

Kahlan attempts to load the `kahlan-config.php` file from the current directory as the default config file. However you can define your own path using the `--config=myconfigfile.php` option in the command line. Custom `--config` can be useful if you want to use some specific configuration for Travis or something else.

Example of a config file:

```php
<?php
use Kahlan\Filter\Filter;
use Kahlan\Reporter\Coverage\Exporter\Coveralls;

// It overrides some default option values.
// Note that the values passed in command line will overwrite the ones below.
$commandLine = $this->commandLine();
$commandLine->argument('ff', 'default', 1);
$commandLine->argument('coverage', 'default', 3);
$commandLine->argument('coverage-scrutinizer', 'default', 'scrutinizer.xml');
$commandLine->argument('coverage-coveralls', 'default', 'coveralls.json');

// The logic to include into the workflow.
Filter::register('kahlan.coveralls', function($chain) {

    // Get the reporter called `'coverage'` from the list of reporters
    $coverage = $this->reporters()->get('coverage');

    // Abort if no coverage is available.
    if (!$coverage || !$this->commandLine()->exists('coverage-coveralls')) {
        return $chain->next();
    }

    // Use the `Coveralls` class to write the JSON coverage into a file
    Coveralls::write([
        'coverage' => $coverage,
        'file' => $this->commandLine()->get('coverage-coveralls'),
        'service_name' => 'travis-ci',
        'service_job_id' => getenv('TRAVIS_JOB_ID') ?: null
    ]);

    // Continue the chain
    return $chain->next();
});

// Apply the logic to the `'reporting'` entry point.
Filter::apply($this, 'reporting', 'kahlan.coveralls');
?>
```

Above `'kahlan.coveralls'` is just a custom name and could be whatever as long as `Filter::register()` and `Filter::apply()` are named consistently.

`$this` refer to the Kahlan instance so `$this->reporters()->get('coverage')` will give you the instance of the coverage reporter. This coverage reporter will contain all raw data which is passed to the `Coveralls` exporter to be formatter.

The filterable entry points are the following:

* `'workflow`'           # The one to rule them all
  * `'interceptor`'      # Operations on the autoloader
  * `'namespaces`'       # Adds some namespaces not managed by composer (like `spec`)
  * `'patchers`'         # Adds patchers
  * `'loadSpecs`'        # Loads specs
  * `'reporters`'        # Adds reporters
    * `'console'`        # Creates the console reporter
    * `'coverage'`       # Creates the coverage reporter
  * `'matchers`'         # Useful for registering some further matchers
  * `'run`'              # Runs the test suite
  * `'reporting`'        # Runs some additional reporting tasks
  * `'stop`'             # Trigger the stop event to reporters
  * `'quit`'             # For some additional post processing before quitting


[You can see more details about how the workflow works here](https://github.com/kahlan/kahlan/blob/master/src/Cli/Kahlan.php) (start reading with the `run()` method).

### Optimizations

Kahlan acts like a wrapper. It intercepts loaded classes Just It Time (i.e. during the autoloading step) and rewrites the source code on the fly to make it easily testable with PHP. That's why Monkey Patching or redefining a class's method can be done inside the testing environment without any PECL extensions like runkit, aop, etc.

Notice that this approach will make your code run a bit slower. However you can optimize Kahlan's interceptor to only patch the namespaces you want.

For example, the following configuration will only limit the patching to a set of namespaces/classes:

```php
$this->commandLine()->set('include', [
    'myapp',
    'lithium',
    'li3_zendserver\data\Job',
    'AuthorizeNetCIM'
]);
```

Conversely you can exclude some external dependencies to improve performance if you don't intend to Monkey Patch/Stub some namespaces/classes:

```php
$this->commandLine()->set('exclude', [
    'Symfony',
    'Doctrine'
]);
```

Finally, you can disable all the patching if you prefer to deal with DI only and are not interested by Kahlan's features:

```php
$this->commandLine()->set('include', []);
```
**Note:** You will still able to stub instances and classes created with `Double::instance()`/`Double::classname()`.
