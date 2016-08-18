<?php
namespace Kahlan\Cli;

use Kahlan\Dir\Dir;
use Kahlan\Jit\Interceptor;
use Kahlan\Filter\Filter;
use Kahlan\Filter\Behavior\Filterable;
use Kahlan\Matcher;
use Kahlan\Jit\Patcher\Pointcut;
use Kahlan\Jit\Patcher\Monkey;
use Kahlan\Jit\Patcher\Rebase;
use Kahlan\Jit\Patcher\Quit;
use Kahlan\Plugin\Quit as QuitStatement;
use Kahlan\Reporters;
use Kahlan\Reporter\Terminal;
use Kahlan\Reporter\Coverage;
use Kahlan\Reporter\Coverage\Driver\Phpdbg;
use Kahlan\Reporter\Coverage\Driver\Xdebug;
use Kahlan\Reporter\Coverage\Exporter\Clover;
use Kahlan\Reporter\Coverage\Exporter\Istanbul;
use Kahlan\Reporter\Coverage\Exporter\Lcov;
use Composer\Script\Event;

class Kahlan {

    use Filterable;

    const VERSION = '2.5.6';

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
     * Warning !
     * This method should only be called by Composer as an attempt to auto clear up caches automatically
     * when the version of Kahlan is updated.
     * It will have no effect if the cache location is changed the default config file (i.e. `'kahlan-config.php'`).
     */
    public static function composerPostUpdate(Event $event)
    {
        if (!defined('DS')) {
            define('DS', DIRECTORY_SEPARATOR);
        }
        $kahlan = new static(['autoloader' => $event->getComposer()]);
        $kahlan->loadConfig();
        $kahlan->_interceptor();
        if ($interceptor = Interceptor::instance()) {
            $interceptor->clearCache();
        }
    }

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

        $args->argument('src',       ['array'   => true, 'default' => ['src']]);
        $args->argument('spec',      ['array'   => true, 'default' => ['spec']]);
        $args->argument('reporter',  ['array'   => true, 'default' => ['dot']]);
        $args->argument('pattern',   ['default' => '*Spec.php']);
        $args->argument('coverage',  ['type'    => 'string']);
        $args->argument('config',    ['default' => 'kahlan-config.php']);
        $args->argument('ff',        ['type'    => 'numeric', 'default' => 0]);
        $args->argument('cc',        ['type'    => 'boolean', 'default' => false]);
        $args->argument('no-colors', ['type'    => 'boolean', 'default' => false]);
        $args->argument('no-header', ['type'    => 'boolean', 'default' => false]);
        $args->argument('include',   [
            'array' => true,
            'default' => ['*'],
            'value' => function($value) {
                return array_filter($value);
            }
        ]);
        $args->argument('exclude',    [
            'array' => true,
            'default' => [],
            'value' => function($value) {
                return array_filter($value);
            }
        ]);
        $args->argument('persistent', ['type'  => 'boolean', 'default' => true]);
        $args->argument('autoclear',  ['array' => true, 'default' => [
            'Kahlan\Plugin\Monkey',
            'Kahlan\Plugin\Call',
            'Kahlan\Plugin\Stub',
            'Kahlan\Plugin\Quit'
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
        $args->argument('config',  ['default'  => 'kahlan-config.php']);
        $args->argument('help',    ['type'     => 'boolean']);
        $args->argument('version', ['type'     => 'boolean']);
        $args->parse($argv);

        $run = function($args) {
            if (file_exists($args->get('config'))) {
                require $args->get('config');
            }
        };
        $run($args);
        $this->_args->parse($argv, false);

        if ($args->get('help')) {
            return $this->_help();
        }

        if ($args->get('version')) {
            return $this->_version();
        }
    }

    /**
     * Gets the default terminal console.
     *
     * @return object The default terminal console.
     */
    protected function _terminal()
    {
        return new Terminal([
            'colors' => !$this->args()->get('no-colors'),
            'header' => !$this->args()->get('no-header')
        ]);
    }

    /**
     * Echoes the Kahlan version.
     */
    protected function _version()
    {
        $terminal = $this->_terminal();
        if (!$this->args()->get('no-header')) {
            $terminal->write($terminal->kahlan() ."\n\n");
            $terminal->write($terminal->kahlanBaseline(), 'd');
            $terminal->write("\n\n");
        }

        $terminal->write("version ");
        $terminal->write(static::VERSION , 'green');
        $terminal->write("\n\n");
        $terminal->write("For additional help you must use ");
        $terminal->write("--help" , 'green');
        $terminal->write("\n\n");
        QuitStatement::quit();
    }

    /**
     * Echoes the help message.
     */
    protected function _help()
    {
        $terminal = $this->_terminal();
        if (!$this->args()->get('no-header')) {
            $terminal->write($terminal->kahlan() ."\n\n");
            $terminal->write($terminal->kahlanBaseline(), 'd');
            $terminal->write("\n\n");
        }
        $help = <<<EOD

Usage: kahlan [options]

Configuration Options:

  --config=<file>                     The PHP configuration file to use (default: `'kahlan-config.php'`).
  --src=<path>                        Paths of source directories (default: `['src']`).
  --spec=<path>                       Paths of specification directories (default: `['spec']`).
  --pattern=<pattern>                 A shell wildcard pattern (default: `'*Spec.php'`).

Reporter Options:

  --reporter=<name>[:<output_file>]   The name of the text reporter to use, the buit-in text reporters
                                      are `'dot'`, `'bar'`, `'json'`, `'tap'` & `'verbose'` (default: `'dot'`).
                                      You can optionally redirect the reporter output to a file by using the
                                      colon syntax (muliple --reporter options are also supported).

Code Coverage Options:

  --coverage=<integer|string>         Generate code coverage report. The value specify the level of
                                      detail for the code coverage report (0-4). If a namespace, class, or
                                      method definition is provided, it will generate a detailed code
                                      coverage of this specific scope (default `''`).
  --clover=<file>                     Export code coverage report into a Clover XML format.
  --istanbul=<file>                   Export code coverage report into an istanbul compatible JSON format.
  --lcov=<file>                       Export code coverage report into a lcov compatible text format.

Test Execution Options:

  --ff=<integer>                      Fast fail option. `0` mean unlimited (default: `0`).
  --no-colors=<boolean>               To turn off colors. (default: `false`).
  --no-header=<boolean>               To turn off header. (default: `false`).
  --include=<string>                  Paths to include for patching. (default: `['*']`).
  --exclude=<string>                  Paths to exclude from patching. (default: `[]`).
  --persistent=<boolean>              Cache patched files (default: `true`).
  --cc=<boolean>                      Clear cache before spec run. (default: `false`).
  --autoclear                         Classes to autoclear after each spec (default: [
                                          `'Kahlan\Plugin\Monkey'`,
                                          `'Kahlan\Plugin\Call'`,
                                          `'Kahlan\Plugin\Stub'`,
                                          `'Kahlan\Plugin\Quit'`
                                      ])

Miscellaneous Options:

  --help                 Prints this usage information.
  --version              Prints Kahlan version

Note: The `[]` notation in default values mean that the related option can accepts an array of values.
To add additionnal values, just repeat the same option many times in the command line.


EOD;
        $terminal->write($help);
        QuitStatement::quit();
    }

    /**
     * Regiter built-in matchers.
     */
    public static function registerMatchers() {
        Matcher::register('toBe',             'Kahlan\Matcher\ToBe');
        Matcher::register('toBeA',            'Kahlan\Matcher\ToBeA');
        Matcher::register('toBeAn',           'Kahlan\Matcher\ToBeA');
        Matcher::register('toBeAnInstanceOf', 'Kahlan\Matcher\ToBeAnInstanceOf');
        Matcher::register('toBeCloseTo',      'Kahlan\Matcher\ToBeCloseTo');
        Matcher::register('toBeEmpty',        'Kahlan\Matcher\ToBeFalsy');
        Matcher::register('toBeFalsy',        'Kahlan\Matcher\ToBeFalsy');
        Matcher::register('toBeGreaterThan',  'Kahlan\Matcher\ToBeGreaterThan');
        Matcher::register('toBeLessThan',     'Kahlan\Matcher\ToBeLessThan');
        Matcher::register('toBeNull',         'Kahlan\Matcher\ToBeNull');
        Matcher::register('toBeTruthy',       'Kahlan\Matcher\ToBeTruthy');
        Matcher::register('toContain',        'Kahlan\Matcher\ToContain');
        Matcher::register('toContainKey',     'Kahlan\Matcher\ToContainKey');
        Matcher::register('toContainKeys',    'Kahlan\Matcher\ToContainKey');
        Matcher::register('toEcho',           'Kahlan\Matcher\ToEcho');
        Matcher::register('toEqual',          'Kahlan\Matcher\ToEqual');
        Matcher::register('toHaveLength',     'Kahlan\Matcher\ToHaveLength');
        Matcher::register('toMatch',          'Kahlan\Matcher\ToMatch');
        Matcher::register('toReceive',        'Kahlan\Matcher\ToReceive');
        Matcher::register('toReceiveNext',    'Kahlan\Matcher\ToReceiveNext');
        Matcher::register('toThrow',          'Kahlan\Matcher\ToThrow');
        Matcher::register('toMatchEcho',      'Kahlan\Matcher\ToMatchEcho');
    }

    /**
     * Run the workflow.
     */
    public function run()
    {
        if (!defined('KAHLAN_FUNCTIONS_EXIST') && (!defined('KAHLAN_DISABLE_FUNCTIONS') || !KAHLAN_DISABLE_FUNCTIONS)) {
            fwrite(STDERR, "Kahlan's global functions are missing because of some naming collisions with another library.\n");
            exit(-1);
        }

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
            if (!$this->args()->exists('coverage')) {
                if ($this->args()->exists('clover') || $this->args()->exists('istanbul') || $this->args()->exists('lcov')) {
                    $this->args()->set('coverage', 1);
                }
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
                'exclude'    => array_merge($this->args()->get('exclude'), ['Kahlan\\']),
                'persistent' => $this->args()->get('persistent'),
                'cachePath'  => rtrim(realpath(sys_get_temp_dir()), DS) . DS . 'kahlan',
                'clearCache' => $this->args()->get('cc')
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
            $patchers->add('pointcut', new Pointcut());
            $patchers->add('monkey',   new Monkey());
            $patchers->add('rebase',   new Rebase());
            $patchers->add('quit',     new Quit());
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
                'exclude' => '*/.*',
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
            $collection = $this->reporters();

            $reporters = $this->args()->get('reporter');
            if (!$reporters) {
                return;
            }

            foreach($reporters as $reporter) {
                $parts = explode(":", $reporter);
                $name = $parts[0];
                $output = isset($parts[1]) ? $parts[1] : null;

                if (!$name === null || $name === 'none') {
                    continue;
                }

                $params = [
                    'start'  => $this->_start,
                    'colors' => !$this->args()->get('no-colors'),
                    'header' => !$this->args()->get('no-header')
                ];

                if (isset($output) && strlen($output) > 0) {
                    if (file_exists($output) && !is_writable($output)) {
                        fwrite(STDERR, "Error: please check that file '{$output}' is writable\n");
                    } else {
                        $file = @fopen($output, 'w');
                        if (!$file) {
                            fwrite(STDERR, "Error: can't create file '{$output}' for write\n");
                        } else {
                            $params['output'] = $file;
                        }
                    }
                }

                $class = 'Kahlan\Reporter\\' . str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', trim($name))));
                $collection->add($name, new $class($params));
            }
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
            $reporters = $this->reporters();
            $driver = null;

            if (PHP_SAPI === 'phpdbg') {
                $driver = new Phpdbg();
            } elseif (extension_loaded('xdebug')) {
                $driver = new Xdebug();
            } else {
                fwrite(STDERR, "ERROR: PHPDBG SAPI has not been detected and Xdebug is not installed, code coverage can't be used.\n");
                exit(-1);
            }
            $coverage = new Coverage([
                'verbosity' => $this->args()->get('coverage') === null ? 1 : $this->args()->get('coverage'),
                'driver' => $driver,
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
            if (!$reporter) {
                return;
            }
            if ($this->args()->exists('clover')) {
                Clover::write([
                    'collector' => $reporter,
                    'file' => $this->args()->get('clover')
                ]);
            }
            if ($this->args()->exists('istanbul')) {
                Istanbul::write([
                    'collector' => $reporter,
                    'file' => $this->args()->get('istanbul')
                ]);
            }
            if ($this->args()->exists('lcov')) {
                Lcov::write([
                    'collector' => $reporter,
                    'file' => $this->args()->get('lcov')
                ]);
            }
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

define('KAHLAN_VERSION', Kahlan::VERSION);
