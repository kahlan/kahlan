<?php
namespace kahlan\spec\suite\cli;

use Exception;
use jit\Interceptor;
use filter\Filter;
use kahlan\Suite;
use kahlan\Matcher;
use kahlan\cli\Kahlan;
use kahlan\plugin\Quit;

describe("Kahlan", function() {

    /**
     * Save current & reinitialize the Interceptor class.
     */
    before(function() {
        $this->previous = Interceptor::instance();
        Interceptor::unpatch();
    });

    /**
     * Restore Interceptor class.
     */
    after(function() {
        Interceptor::load($this->previous);
    });

    beforeEach(function() {
        $this->specs = new Kahlan([
            'autoloader' => Interceptor::composer()[0],
            'suite' => new Suite([
                'matcher' => new Matcher()
            ])
        ]);
    });

    describe("->loadConfig()", function() {

        it("sets passed arguments to specs", function() {

            $args = [
                '--src=src',
                '--spec=spec/fixture/kahlan/spec',
                '--pattern=*MySpec.php',
                '--reporter=verbose',
                '--coverage=3',
                '--config=spec/fixture/kahlan/kahlan-config.php',
                '--ff=5',
                '--cc',
                '--no-colors',
                '--no-header',
                '--include=*',
                '--exclude=kahlan\\',
                '--persistent=false',
                '--autoclear=kahlan\plugin\Monkey',
                '--autoclear=kahlan\plugin\Call',
                '--autoclear=kahlan\plugin\Stub',
                '--autoclear=kahlan\plugin\Quit'
            ];

            $this->specs->loadConfig($args);
            expect($this->specs->args()->get())->toBe([
                'src'        => ['src'],
                'spec'       => ['spec/fixture/kahlan/spec'],
                'pattern'    => "*MySpec.php",
                'reporter'   => "verbose",
                'config'     => "spec/fixture/kahlan/kahlan-config.php",
                'ff'         => 5,
                'cc'         => true,
                'no-colors'  => true,
                'no-header'  => true,
                'include'    => ['*'],
                'exclude'    => ['kahlan\\'],
                'persistent' => false,
                'autoclear'  => [
                    'kahlan\plugin\Monkey',
                    'kahlan\plugin\Call',
                    'kahlan\plugin\Stub',
                    'kahlan\plugin\Quit'
                ],
                'coverage'   => '3'
            ]);

        });

        it("loads the config file", function() {

            $this->specs->loadConfig([
                '--spec=spec/fixture/kahlan/spec/PassTest.php',
                '--config=spec/fixture/kahlan/kahlan-config.php',
                '--pattern=*Test.php',
                '--reporter=none'
            ]);
            $this->specs->run();

            expect($this->specs->suite()->loaded)->toBe(true);

            Interceptor::unpatch();

        });

        it("echoes version if --version if provided", function() {

            $version = Kahlan::VERSION;

            $expected = <<<EOD
            _     _
  /\ /\__ _| |__ | | __ _ _ __
 / //_/ _` | '_ \| |/ _` | '_ \
/ __ \ (_| | | | | | (_| | | | |
\/  \/\__,_|_| |_|_|\__,_|_| |_|

\033[2;39;49mThe Unit/BDD PHP Test Framework for Freedom, Truth, and Justice.\033[0m

version \033[0;32;49m{$version}\033[0m

For additional help you must use \033[0;32;49m--help\033[0m


EOD;

            $closure = function() {
                try {
                    $this->specs->loadConfig(['--version']);
                } catch (Exception $e) {}
            };

            Quit::disable();
            expect($closure)->toEcho($expected);

        });

        it("echoes the help if --help is provided", function() {

            $help = <<<EOD
            _     _
  /\ /\__ _| |__ | | __ _ _ __
 / //_/ _` | '_ \| |/ _` | '_ \
/ __ \ (_| | | | | | (_| | | | |
\/  \/\__,_|_| |_|_|\__,_|_| |_|

\033[2;39;49mThe Unit/BDD PHP Test Framework for Freedom, Truth, and Justice.\033[0m


Usage: kahlan [options]

Configuration Options:

  --config=<file>                     The PHP configuration file to use (default: `'kahlan-config.php'`).
  --src=<path>                        Paths of source directories (default: `['src']`).
  --spec=<path>                       Paths of specifications directories (default: `['spec']`).
  --pattern=<pattern>                 A shell wildcard pattern (default: `'*Spec.php'`).

Reporter Options:

  --reporter=<string>                 The name of the text reporter to use, the buit-in text reporters
                                      are `'dot'` & `'bar'` (default: `'dot'`).

Code Coverage Options:

  --coverage=<integer|string>         Generate code coverage report. The value specify the level of
                                      detail for the code coverage report (0-4). If a namespace, class or
                                      method definition is provided, if will generate a detailled code
                                      coverage of this specific scope (default `''`).
  --clover=<file>                     Export code coverage report into a Clover XML format.

Test Execution Options:

  --ff=<integer>                      Fast fail option. `0` mean unlimited (default: `0`).
  --no-colors=<boolean>               To turn off colors. (default: `false`).
  --no-header=<boolean>               To turn off header. (default: `false`).
  --include=<string>                  Paths to include for patching. (default: `['*']`).
  --exclude=<string>                  Paths to exclude from patching. (default: `[]`).
  --persistent=<boolean>              Cache patched files (default: `true`).
  --cc=<boolean>                      Clear cache before spec run. (default: `false`).
  --autoclear                         Classes to autoclear after each spec (default: [
                                          `'kahlan\plugin\Monkey'`,
                                          `'kahlan\plugin\Call'`,
                                          `'kahlan\plugin\Stub'`,
                                          `'kahlan\plugin\Quit'`
                                      ])

Miscellaneous Options:

  --help                 Prints this usage information.
  --version              Prints Kahlan version

Note: The `[]` notation in default values mean that the related option can accepts an array of values.
To add additionnal values, just repeat the same option many times in the command line.


EOD;

            $closure = function() {
                try {
                    $this->specs->loadConfig(['--help']);
                } catch (Exception $e) {

                }
            };

            Quit::disable();
            expect($closure)->toEcho($help);

        });

        it("doesn't display header with --no-header", function() {

            $version = Kahlan::VERSION;

            $message = <<<EOD
version \033[0;32;49m{$version}\033[0m

For additional help you must use \033[0;32;49m--help\033[0m


EOD;

            $closure = function() {
                try {
                    $this->specs->loadConfig(['--version', '--no-header']);
                } catch (Exception $e) {}
            };

            Quit::disable();
            expect($closure)->toEcho($message);

        });

        it("doesn't filter empty string from include & exclude", function() {

            $args = [
                '--include=',
                '--exclude=',
            ];

            $this->specs->loadConfig($args);
            expect($this->specs->args()->get()['include'])->toBe([]);
            expect($this->specs->args()->get()['exclude'])->toBe([]);

        });

    });

    describe("->run()", function() {

        it("runs a spec which pass", function() {

            $this->specs->loadConfig([
                '--spec=spec/fixture/kahlan/spec/PassTest.php',
                '--pattern=*Test.php',
                '--reporter=none'
            ]);
            $this->specs->run();
            expect($this->specs->suite()->total())->toBe(1);
            expect($this->specs->status())->toBe(0);

            Interceptor::unpatch();

        });

        it("runs a spec which fail", function() {

            $this->specs->loadConfig([
                '--spec=spec/fixture/kahlan/spec/FailTest.php',
                '--pattern=*Test.php',
                '--reporter=none'
            ]);
            $this->specs->run();
            expect($this->specs->suite()->total())->toBe(1);
            expect($this->specs->status())->toBe(-1);

            Interceptor::unpatch();

        });

        it("runs filters in the correct order", function() {

            $this->specs->loadConfig([
                '--spec=spec/fixture/kahlan/spec/PassTest.php',
                '--pattern=*Test.php',
                '--reporter=none'
            ]);

            $order = [];

            Filter::register('spec.bootstrap', function($chain) use (&$order) { $order[] = 'bootstrap';});
            Filter::apply($this->specs, 'bootstrap', 'spec.bootstrap');

            Filter::register('spec.interceptor', function($chain) use (&$order) { $order[] = 'interceptor';});
            Filter::apply($this->specs, 'interceptor', 'spec.interceptor');

            Filter::register('spec.namespaces', function($chain) use (&$order) { $order[] = 'namespaces';});
            Filter::apply($this->specs, 'namespaces', 'spec.namespaces');

            Filter::register('spec.patchers', function($chain) use (&$order) { $order[] = 'patchers';});
            Filter::apply($this->specs, 'patchers', 'spec.patchers');

            Filter::register('spec.load', function($chain) use (&$order) { $order[] = 'load';});
            Filter::apply($this->specs, 'load', 'spec.load');

            Filter::register('spec.reporters', function($chain) use (&$order) { $order[] = 'reporters';});
            Filter::apply($this->specs, 'reporters', 'spec.reporters');

            Filter::register('spec.matchers', function($chain) use (&$order) { $order[] = 'matchers';});
            Filter::apply($this->specs, 'matchers', 'spec.matchers');

            Filter::register('spec.run', function($chain) use (&$order) { $order[] = 'run';});
            Filter::apply($this->specs, 'run', 'spec.run');

            Filter::register('spec.reporting', function($chain) use (&$order) { $order[] = 'reporting';});
            Filter::apply($this->specs, 'reporting', 'spec.reporting');

            Filter::register('spec.stop', function($chain) use (&$order) { $order[] = 'stop';});
            Filter::apply($this->specs, 'stop', 'spec.stop');

            Filter::register('spec.quit', function($chain) use (&$order) { $order[] = 'quit';});
            Filter::apply($this->specs, 'quit', 'spec.quit');

            $this->specs->run();

            expect($order)->toBe([
                'bootstrap',
                'interceptor',
                'namespaces',
                'patchers',
                'load',
                'reporters',
                'matchers',
                'run',
                'reporting',
                'stop',
                'quit'
            ]);

            Interceptor::unpatch();

        });

    });

});