<?php
namespace kahlan\cli;

use Exception;
use box\Box;
use dir\Dir;
use filter\Filter;
use filter\behavior\Filterable;
use kahlan\Suite;
use kahlan\cli\Cli;
use kahlan\cli\Args;
use kahlan\jit\Interceptor;
use kahlan\jit\Patchers;
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
use kahlan\reporter\coverage\exporter\Scrutinizer;

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
     * The patcher container.
     *
     * @var object
     */
    protected $_patchers = null;

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

        $this->_patchers = new Patchers();
        $this->_reporters = new Reporters();
        $this->_args = $args = new Args();

        $args->option('src', ['array' => 'true', 'default' => ['src']]);
        $args->option('spec', ['array' => 'true', 'default' => ['spec']]);
        $args->option('reporter', ['default' => 'dot']);
        $args->option('coverage', ['type' => 'string','default' => '0']);
        $args->option('config', ['default' => 'kahlan-config.php']);
        $args->option('ff', ['type' => 'numeric', 'default' => 0]);
        $args->option('no-colors', ['type' => 'boolean', 'default' => false]);
        $args->option('include', ['array' => 'true', 'default' => ['*']]);
        $args->option('exclude', ['array' => 'true', 'default' => []]);
        $args->option('persistent', ['type'  => 'boolean', 'default' => true]);
        $args->option('autoclear', ['array' => 'true', 'default' => [
            'kahlan\plugin\Monkey',
            'kahlan\plugin\Call',
            'kahlan\plugin\Stub',
            'kahlan\plugin\Quit',
            'kahlan\plugin\DummyClass'
        ]]);
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
     * Returns the suite.
     *
     * @return object
     */
    public function suite()
    {
        return $this->_suite;
    }

    /**
     * Returns the patcher container.
     *
     * @return object
     */
    public function patchers()
    {
        return $this->_patchers;
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
     * @param string $argv The command line string
     */
    public function loadConfig($argv = [])
    {
        $this->_args->parse($argv);

        if ($this->_args->get('help')) {
            return $this->_help();
        }

        if (file_exists($this->_args->get('config'))) {
            require $this->_args->get('config');
        }
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

Reporter Options:

  --reporter=<string>                 The name of the text reporter to use, the buit-in text reporters
                                      are `'dot'` & `'bar'` (default: `'dot'`).

Code Coverage Options:

  --coverage=<integer|string>         Generate code coverage report. The value specify the level of
                                      detail for the code coverage report (0-4). If a namespace, class or
                                      method definition is provided, if will generate a detailled code
                                      coverage of this specific scope (default `0`).
  --scrutinizer=<file>                Export code coverage report into a Scrutinizer compatible format.

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
    exit();
    }

    /**
     * Run the workflow.
     */
    public function run()
    {
        $this->_start = microtime(true);
        return Filter::on($this, 'workflow', [], function($chain) {

            $this->_namespaces();

            $this->_patchers();

            $this->_interceptor();

            $this->_load();

            $this->_reporters();

            $this->_start();

            $this->_run();

            $this->_reporting();

            $this->_stop();
        });
    }

    public function status()
    {
        return $this->suite()->status();
    }

    /**
     * Set up the default `'namespace'` filter.
     */
    protected function _namespaces()
    {
        return Filter::on($this, 'namespaces', [], function($chain) {
            if (!$this->_autoloader || !method_exists($this->_autoloader, 'add')) {
                echo Cli::color("The defined autoloader doesn't support `add()` calls\n", 'yellow');
                return;
            }
            $paths = $this->args()->get('spec');
            foreach ($paths as $path) {
                $path = realpath($path);
                $namespace = basename($path) . '\\';
                $this->_autoloader->add($namespace, dirname($path));
            }
        });
    }

    /**
     * Set up the default `'patcher'` filter.
     */
    protected function _patchers()
    {
        return Filter::on($this, 'patchers', [], function($chain) {
            $patchers = $this->patchers();
            $patchers->add('substitute', new DummyClass(['namespaces' => ['spec\\']]));
            $patchers->add('pointcut', new Pointcut());
            $patchers->add('monkey', new Monkey());
            $patchers->add('rebase', new Rebase());
            $patchers->add('quit', new Quit());
            return $patchers;
        });
    }

    /**
     * Set up the default `'interceptor'` filter.
     */
    protected function _interceptor()
    {
        return Filter::on($this, 'autoloader', [], function($chain) {
            Interceptor::patch([
                'loader' => [$this->_autoloader, 'loadClass'],
                'patchers' => $this->patchers(),
                'include' => $this->args()->get('include'),
                'exclude' => $this->args()->get('exclude'),
                'persistent' => $this->args()->get('persistent')
            ]);
        });
    }

    /**
     * Set up the default `'load'` filter.
     */
    protected function _load()
    {
        return Filter::on($this, 'load', [], function($chain) {
            $files = Dir::scan([
                'path' => $this->args()->get('spec'),
                'include' => '*Spec.php',
                'type' => 'file'
            ]);
            foreach($files as $file) {
                require $file;
            }
        });
    }

    /**
     * Set up the default `'reporters'` filter.
     */
    protected function _reporters()
    {
        return Filter::on($this, 'reporters', [], function($chain) {
            $this->_console();
            if ($this->args()->exists('coverage')) {
                $this->_coverage();
            }
        });
    }

    /**
     * Set up the default `'console'` filter.
     */
    protected function _console()
    {
        return Filter::on($this, 'console', [], function($chain) {
            $reporters = $this->reporters();
            $start = $this->_start;
            $colors = !$this->args()->get('no-colors');
            if ($this->args()->get('reporter') === 'dot') {
                $reporter = new Dot(compact('start', 'colors'));
            }
            if ($this->args()->get('reporter') === 'bar') {
                $reporter = new Bar(compact('start', 'colors'));
            }
            if ($reporter) {
                $reporters->add('console', $reporter);
            }
        });
    }

    /**
     * Set up the default `'coverage'` filter.
     */
    protected function _coverage()
    {
        return Filter::on($this, 'coverage', [], function($chain) {
            $reporters = $this->reporters();
            $coverage = new Coverage([
                'verbosity' => $this->args()->get('coverage'),
                'driver' => new Xdebug(),
                'path' => $this->args()->get('src'),
                'colors' => !$this->args()->get('no-colors')
            ]);
            $reporters->add('coverage', $coverage);
        });
    }

    /**
     * Set up the default `'start'` filter.
     */
    protected function _start()
    {
        return Filter::on($this, 'start', [], function($chain) {});
    }

    /**
     * Set up the default `'run'` filter.
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
     * Set up the default `'reporting'` filter.
     */
    protected function _reporting()
    {
        return Filter::on($this, 'reporting', [], function($chain) {
            $coverage = $this->reporters()->get('coverage');
            if ($coverage && $this->args()->exists('scrutinizer')) {
                Scrutinizer::write([
                    'coverage' => $coverage,
                    'file' => $this->args()->get('scrutinizer')
                ]);
            }
        });
    }

    /**
     * Set up the default `'stop'` filter.
     */
    protected function _stop()
    {
        return Filter::on($this, 'stop', [], function($chain) {});
    }

}
