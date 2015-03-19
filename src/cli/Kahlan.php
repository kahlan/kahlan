<?php
namespace kahlan\cli;

use Exception;
use box\Box;
use dir\Dir;
use jit\Interceptor;
use jit\Patchers;
use filter\Filter;
use filter\behavior\Filterable;
use kahlan\Suite;
use kahlan\Matcher;
use kahlan\cli\Cli;
use kahlan\cli\Args;
use kahlan\jit\patcher\DummyClass;
use kahlan\jit\patcher\Pointcut;
use kahlan\jit\patcher\Monkey;
use kahlan\jit\patcher\Rebase;
use kahlan\jit\patcher\Quit;
use kahlan\Reporters;
use kahlan\reporter\Dot;
use kahlan\reporter\Bar;
use kahlan\reporter\Coverage;
use kahlan\reporter\coverage\driver\Xdebug;
use kahlan\reporter\coverage\driver\HHVM;
use kahlan\reporter\coverage\exporter\Clover;

class Kahlan {

    use Filterable;

    /**
     * Starting time.
     *
     * @var float
     */
    protected $_start = 0;

    /**
     * The suite instance.
     *
     * @var object
     */
    protected $_suite = null;

    /**
     * The runtime autoloader.
     *
     * @var object
     */
    protected $_autoloader = null;

    /**
     * The reporter container.
     *
     * @var object
     */
    protected $_reporters = null;

    /**
     * The arguments.
     *
     * @var object
     */
    protected $_args = null;

    /**
     * The Constructor.
     *
     * @param array $options Possible options are:
     *                       - `'autoloader'` _object_ : The autoloader instance.
     *                       - `'suite'`      _object_ : The suite instance.
     */
    public function __construct($options = [])
    {
        $defaults = ['autoloader' => null, 'suite' => null];
        $options += $defaults;

        $this->_autoloader = $options['autoloader'];
        $this->_suite = $options['suite'];

        $this->_reporters = new Reporters();
        $this->_args = $args = new Args();

        $args->argument('src',        ['array'   => 'true', 'default' => ['src']]);
        $args->argument('spec',       ['array'   => 'true', 'default' => ['spec']]);
        $args->argument('pattern',    ['default' => '*Spec.php']);
        $args->argument('reporter',   ['default' => 'dot']);
        $args->argument('coverage',   ['type'    => 'string']);
        $args->argument('config',     ['default' => 'kahlan-config.php']);
        $args->argument('ff',         ['type'    => 'numeric', 'default' => 0]);
        $args->argument('no-colors',  ['type'    => 'boolean', 'default' => false]);
        $args->argument('include',    [
            'array' => 'true',
            'default' => ['*'],
            'value' => function($value) {
                return array_filter($value);
            }
        ]);
        $args->argument('exclude',    [
            'array' => 'true',
            'default' => [],
            'value' => function($value) {
                return array_filter($value);
            }
        ]);
        $args->argument('persistent', ['type'  => 'boolean', 'default' => true]);
        $args->argument('autoclear',  ['array' => 'true', 'default' => [
            'kahlan\plugin\Monkey',
            'kahlan\plugin\Call',
            'kahlan\plugin\Stub',
            'kahlan\plugin\Quit',
            'kahlan\plugin\DummyClass'
        ]]);
    }

    /**
     * Returns the attached autoloader instance.
     *
     * @return object
     */
    public function autoloader()
    {
        return $this->_autoloader;
    }

    /**
     * Returns arguments instance.
     *
     * @return object
     */
    public function args()
    {
        return $this->_args;
    }

    /**
     * Returns the suite instance.
     *
     * @return object
     */
    public function suite()
    {
        return $this->_suite;
    }

    /**
     * Returns the reporter container.
     *
     * @return object
     */
    public function reporters()
    {
        return $this->_reporters;
    }

    /**
     * Load the config.
     *
     * @param string $argv The command line string.
     */
    public function loadConfig($argv = [])
    {
        $args = new Args();
        $args->argument('config', ['default' => 'kahlan-config.php']);
        $args->argument('help',   ['type'    => 'boolean']);
        $args->parse($argv);

        if ($args->get('help')) {
            return $this->_help();
        }

        if (file_exists($args->get('config'))) {
            require $args->get('config');
        }

        $this->_args->parse($argv, false);
    }

    /**
     * Echoes the help.
     *
     * @return string
     */
    protected function _help()
    {
        echo <<<EOD
Kahlan - PHP Testing Framework

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
  --include=<string>                  Paths to include for patching. (default: `['*']`).
  --exclude=<string>                  Paths to exclude from patching. (default: `[]`).
  --persistent=<boolean>              Cache patched files (default: `true`).
  --autoclear                         classes to autoclear after each spec (default: [
                                          `'kahlan\plugin\Monkey'`,
                                          `'kahlan\plugin\Call'`,
                                          `'kahlan\plugin\Stub'`,
                                          `'kahlan\plugin\Quit'`,
                                          `'kahlan\plugin\DummyClass'`
                                      ])

Miscellaneous Options:

  --help                 Prints this usage information.

Note: The `[]` notation in default values mean that the related option can accepts an array of values.
To add additionnal values, just repeat the same option many times in the command line.

EOD;
        \kahlan\plugin\Quit::quit();
    }

    /**
     * Regiter built-in matchers.
     */
    public static function registerMatchers() {
        Matcher::register('toBe', 'kahlan\matcher\ToBe');
        Matcher::register('toBeA', 'kahlan\matcher\ToBeA');
        Matcher::register('toBeAn', 'kahlan\matcher\ToBeA');
        Matcher::register('toBeAnInstanceOf', 'kahlan\matcher\ToBeAnInstanceOf');
        Matcher::register('toBeCloseTo', 'kahlan\matcher\ToBeCloseTo');
        Matcher::register('toBeEmpty', 'kahlan\matcher\ToBeFalsy');
        Matcher::register('toBeFalsy', 'kahlan\matcher\ToBeFalsy');
        Matcher::register('toBeGreaterThan', 'kahlan\matcher\ToBeGreaterThan');
        Matcher::register('toBeLessThan', 'kahlan\matcher\ToBeLessThan');
        Matcher::register('toBeNull', 'kahlan\matcher\ToBeNull');
        Matcher::register('toBeTruthy', 'kahlan\matcher\ToBeTruthy');
        Matcher::register('toContain', 'kahlan\matcher\ToContain');
        Matcher::register('toEcho', 'kahlan\matcher\ToEcho');
        Matcher::register('toEqual', 'kahlan\matcher\ToEqual');
        Matcher::register('toHaveLength', 'kahlan\matcher\ToHaveLength');
        Matcher::register('toMatch', 'kahlan\matcher\ToMatch');
        Matcher::register('toReceive', 'kahlan\matcher\ToReceive');
        Matcher::register('toReceiveNext', 'kahlan\matcher\ToReceiveNext');
        Matcher::register('toThrow', 'kahlan\matcher\ToThrow');
    }

    /**
     * Run the workflow.
     */
    public function run()
    {
        $this->_start = microtime(true);
        return Filter::on($this, 'workflow', [], function($chain) {

            $this->_bootstrap();

            $this->_interceptor();

            $this->_namespaces();

            $this->_patchers();

            $this->_load();

            $this->_reporters();

            $this->_matchers();

            $this->_run();

            $this->_reporting();

            $this->_stop();

            $this->_quit();
        });
    }

    /**
     * Returns the exit status.
     *
     * @return integer The exit status.
     */
    public function status()
    {
        return $this->suite()->status();
    }

    /**
     * The default `'bootstrap'` filter.
     */
    protected function _bootstrap()
    {
        return Filter::on($this, 'bootstrap', [], function($chain) {
            $this->suite()->backtraceFocus($this->args()->get('pattern'));
            if ($this->args()->exists('clover') && !$this->args()->exists('coverage')) {
                $this->args()->set('coverage', 1);
            }
        });
    }

    /**
     * The default `'interceptor'` filter.
     */
    protected function _interceptor()
    {
        return Filter::on($this, 'interceptor', [], function($chain) {
            Interceptor::patch([
                'loader'     => [$this->autoloader(), 'loadClass'],
                'include'    => $this->args()->get('include'),
                'exclude'    => array_merge($this->args()->get('exclude'), ['kahlan\\']),
                'persistent' => $this->args()->get('persistent'),
                'cachePath'  => rtrim(realpath(sys_get_temp_dir()), DS) . DS . 'kahlan'
            ]);
        });
    }

    /**
     * The default `'namespace'` filter.
     */
    protected function _namespaces()
    {
        return Filter::on($this, 'namespaces', [], function($chain) {
            $paths = $this->args()->get('spec');
            foreach ($paths as $path) {
                $path = realpath($path);
                $namespace = basename($path) . '\\';
                $this->autoloader()->add($namespace, dirname($path));
            }
        });
    }

    /**
     * The default `'patcher'` filter.
     */
    protected function _patchers()
    {
        return Filter::on($this, 'patchers', [], function($chain) {
            if (!$interceptor = Interceptor::instance()) {
                return;
            }
            $patchers = $interceptor->patchers();
            $patchers->add('substitute', new DummyClass(['namespaces' => ['spec\\']]));
            $patchers->add('pointcut', new Pointcut());
            $patchers->add('monkey', new Monkey());
            $patchers->add('rebase', new Rebase());
            $patchers->add('quit', new Quit());
        });
    }

    /**
     * The default `'load'` filter.
     */
    protected function _load()
    {
        return Filter::on($this, 'load', [], function($chain) {
            $files = Dir::scan($this->args()->get('spec'), [
                'include' => $this->args()->get('pattern'),
                'type' => 'file'
            ]);
            foreach($files as $file) {
                require $file;
            }
        });
    }

    /**
     * The default `'reporters'` filter.
     */
    protected function _reporters()
    {
        return Filter::on($this, 'reporters', [], function($chain) {
            $this->_console();
            $this->_coverage();
        });
    }

    /**
     * The default `'console'` filter.
     */
    protected function _console()
    {
        return Filter::on($this, 'console', [], function($chain) {
            $reporters = $this->reporters();

            $reporter = $this->args()->get('reporter');
            if ($reporter === 'none') {
                return;
            }
            $class = 'kahlan\reporter\\' . str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', $reporter)));

            $reporter = new $class([
                'start'   => $this->_start,
                'colors'  => !$this->args()->get('no-colors')
            ]);
            $reporters->add('console', $reporter);
        });
    }

    /**
     * The default `'coverage'` filter.
     */
    protected function _coverage()
    {
        return Filter::on($this, 'coverage', [], function($chain) {
            if (!$this->args()->exists('coverage')) {
                return;
            }
            if (!extension_loaded('xdebug')) {
                $console = $this->reporters()->get('console');
                $console->write("\nWARNING: Xdebug is not installed, code coverage has been disabled.\n", 'yellow');
                return;
            }
            $reporters = $this->reporters();
            $coverage = new Coverage([
                'verbosity' => $this->args()->get('coverage') === null ? 1 : $this->args()->get('coverage'),
                'driver' => new Xdebug(),
                'path' => $this->args()->get('src'),
                'colors' => !$this->args()->get('no-colors')
            ]);
            $reporters->add('coverage', $coverage);
        });
    }

    /**
     * The default `'matchers'` filter.
     */
    protected function _matchers()
    {
        return Filter::on($this, 'matchers', [], function($chain) {
            static::registerMatchers();
        });
    }

    /**
     * The default `'run'` filter.
     */
    protected function _run()
    {
        return Filter::on($this, 'run', [], function($chain) {
            $this->suite()->run([
                'reporters' => $this->reporters(),
                'autoclear' => $this->args()->get('autoclear'),
                'ff'        => $this->args()->get('ff')
            ]);
        });
    }

    /**
     * The default `'reporting'` filter.
     */
    protected function _reporting()
    {
        return Filter::on($this, 'reporting', [], function($chain) {
            $reporter = $this->reporters()->get('coverage');
            if (!$reporter || !$this->args()->exists('clover')) {
                return;
            }
            Clover::write([
                'collector' => $reporter,
                'file' => $this->args()->get('clover')
            ]);
        });
    }

    /**
     * The default `'stop'` filter.
     */
    protected function _stop()
    {
        return Filter::on($this, 'stop', [], function($chain) {
            $this->suite()->stop();
        });
    }


    /**
     * The default `'quit'` filter.
     */
    protected function _quit()
    {
        return Filter::on($this, 'quit', [$this->suite()->passed()], function($chain, $success) {
        });
    }

}
