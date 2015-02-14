## Reporters

Kahlan provides a flexible reporter system which can be extended easily.

There are three build-in reporters and the default is the dotted one:

```php
./bin/kahlan --reporter=dot # Default value
```

To use a reporter which looks like more a progress bar use the following option:
```php
./bin/kahlan --reporter=bar
./bin/kahlan --reporter=verbose
```

You can easily roll you own if these reporters don't fit your needs.

For example, if you want a console based reporter, create a PHP class which extends `kahlan\reporter\Terminal`. The `Terminal` class offers some useful methods like `write()` for doing some echos on the terminal. But if you wanted to create some kind of JSON reporter extending from `kahlan\reporter\Reporter` would be enough.

Example of a custom console reporter:
```php
<?php
namespace my\namespace;

class MyReporter extends \kahlan\reporter\Terminal
{
    /**
     * Callback called on successful expectation.
     *
     * @param object $report An expect report object.
     */
    public function pass($report = null)
    {
        $this->write('✓', "green");
    }

    /**
     * Callback called on failure.
     *
     * @param object $report An expect report object.
     */
    public function fail($report = null)
    {
        $this->write('☠', "red");
        $this->write("\n");
        $this->_report($report);
    }

    /**
     * Callback called when an exception occur.
     *
     * @param object $report An expect report object.
     */
    public function exception($report = null)
    {
        $this->write('☠', "magenta");
        $this->write("\n");
        $this->_report($report);
    }

    /**
     * Callback called on a skipped spec.
     *
     * @param object $report An expect report object.
     */
    public function skip($report = null)
    {
        $this->write('-', "cyan");
    }

    /**
     * Callback called at the end of specs processing.
     */
    public function end($results = [])
    {
        $this->write("\n");
        $this->_summary($results);
        $this->_focused($results);
    }
}
?>
```

**Note:** `_report()` and `_summary()` are also two inherited methods. Their roles are to format errors and to display a summary of passed tests respectively. Feel free to dig into the source code if you want some more specific output for that.

The next step is to register your new reporter so you'll need to create you own custom [config file](config-file.md)).

Example of config file:
```php
<?php
use filter\Filter;
use my\namespace\reporter\MyReporter;

// The logic to inlude into the workflow.
Filter::register('kahlan.myconsole', function($chain) {
    $reporters = $this->reporters();
    $reporters->add('myconsole', new MyReporter(['start' => $this->_start));
});

// Apply our logic to the `'console'` entry point.
Filter::apply($this, 'console', 'kahlan.myconsole');
?>
```

`$this->_start` is the timestamp in micro seconds of when the process has been started. If passed to reporter, it'll be able to display an accurate execution time.

**Note:** `'myconsole'` is an arbitrary name, it can be anything.

Let's run it:
```php
./bin/kahlan --config=my-config.php
```
![custom_reporter](assets/custom_reporter.png)

A bit ugly, but the check marks and the skulls are present.
