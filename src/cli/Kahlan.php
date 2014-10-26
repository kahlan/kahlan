<?php
/**
 * Kahlan: PHP Testing Framework
 *
 * @copyright     Copyright 2013, CrysaLEAD
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

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
use kahlan\jit\patcher\Substitute;
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
     * Starting time
     *
     * @var float
     */
    protected $_start = 0;

    protected $_suite = null;

    protected $_autoloader = null;

    protected $_patchers = null;

    protected $_reporters = null;

    protected $_args = null;

    public function __construct($options = [])
    {
        $defaults = ['autoloader' => null, 'suite' => null];
        $options += $defaults;
        $this->_patchers = new Patchers();
        $this->_reporters = new Reporters();
        $this->_autoloader = $options['autoloader'];
        $this->_suite = $options['suite'];
    }

    public function loadConfig($argv = [])
    {
        $args = $this->_args = new Args();
        $args->option('src', ['array' => 'true', 'default' => 'src']);
        $args->option('spec', ['array' => 'true', 'default' => 'spec']);
        $args->option('reporter', ['default' => 'dot']);
        $args->option('coverage', ['type' => 'numeric','default' => 0]);
        $args->option('config', ['default' => 'kahlan-config.php']);
        $args->option('ff', ['type' => 'numeric', 'default' => 0]);
        $args->option('no-colors', ['type' => 'boolean', 'default' => false]);
        $args->option('interceptor-include', ['array' => 'true', 'default' => '*']);
        $args->option('interceptor-exclude', ['array' => 'true', 'default' => '']);
        $args->option('interceptor-persistent', ['type'  => 'boolean', 'default' => true]);
        $args->option('autoclear', ['array' => 'true', 'default' => [
            'kahlan\plugin\Monkey',
            'kahlan\plugin\Call',
            'kahlan\plugin\Stub'
        ]]);

        $args->parse($argv);

        if ($args->get('help')) {
            return $this->_help();
        }

        if (file_exists($args->get('config'))) {
            require $args->get('config');
        }
    }

    protected function _help() {
        echo <<<EOD
Kahlan - PHP Testing Framework

Usage: kahlan [options]

Configuration Options:

  --config=<file>                     The PHP configuration file to use (default: `'kahlan-config.php'`).
  --src=<path>                        The path of the source directory (default: `'src'`).
  --spec=<path>                       The path of the specifications directory (default: `'spec'`).

Reporter Options:

  --reporter=<string>                 The name of the text reporter to use, the buit-in text reporters
                                      are `'dot'` & `'bar'` (default: `'dot'`).

Code Coverage Options:

  --coverage=<integer>                Generate code coverage report. The value specify the level of
                                      detail for the code coverage report (0-4) (default `0`).
  --coverage-scrutinizer=<file>       Export code coverage report in Scrutinizer format.

Test Execution Options:

  --ff=<integer>                      Fast fail option. `0` mean unlimited (default: `0`).
  --no-colors=<boolean>               To turn off colors. (default: `false`).
  --interceptor-include=<string>      Paths to include for patching. (default: `'*'`).
  --interceptor-exclude=<string>      Paths to exclude from patching. (default: `''`).
  --interceptor-persistent=<boolean>  Cache patched files (default: `true`).
  --autoclear                         classes to autoclear after each spec (default: `'kahlan\plugin\Monkey'`,
                                      `'kahlan\plugin\Call'`, `'kahlan\plugin\Stub'`)

Miscellaneous Options:

  --help                 Prints this usage information.


EOD;
    exit();
    }

    public function customNamespaces()
    {
        return Filter::on($this, __FUNCTION__, [], function($chain) {
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

    public function initPatchers()
    {
        return Filter::on($this, __FUNCTION__, [], function($chain) {
            $patchers = $this->patchers();
            $patchers->add('substitute', new Substitute(['namespaces' => ['spec\\']]));
            $patchers->add('pointcut', new Pointcut());
            $patchers->add('monkey', new Monkey());
            $patchers->add('rebase', new Rebase());
            $patchers->add('quit', new Quit());
            return $patchers;
        });
    }

    public function patchAutoloader()
    {
        return Filter::on($this, __FUNCTION__, [], function($chain) {
            Interceptor::patch([
                'loader' => [$this->_autoloader, 'loadClass'],
                'patchers' => $this->patchers(),
                'include' => $this->args()->get('interceptor-include'),
                'exclude' => $this->args()->get('interceptor-exclude'),
                'persistent' => $this->args()->get('interceptor-persistent')
            ]);
        });
    }

    public function loadSpecs()
    {
        return Filter::on($this, __FUNCTION__, [], function($chain) {
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

    public function initReporters()
    {
        return Filter::on($this, __FUNCTION__, [], function($chain) {
            $this->consoleReporter();
            if ($this->args()->get('coverage')) {
                $this->coverageReporter();
            }
        });
    }

    public function consoleReporter()
    {
        return Filter::on($this, __FUNCTION__, [], function($chain) {
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

    public function coverageReporter()
    {
        return Filter::on($this, __FUNCTION__, [], function($chain) {
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

    public function preProcess()
    {
        return Filter::on($this, __FUNCTION__, [], function($chain) {
        });
    }

    public function runSpecs()
    {
        return Filter::on($this, __FUNCTION__, [], function($chain) {
            $this->suite()->run([
                'reporters' => $this->reporters(),
                'autoclear' => $this->args()->get('autoclear'),
                'ff'        => $this->args()->get('ff')
            ]);
        });
    }

    public function postProcess()
    {
        return Filter::on($this, __FUNCTION__, [], function($chain) {
            $coverage = $this->reporters()->get('coverage');
            if ($coverage && $this->args()->get('coverage-scrutinizer')) {
                Scrutinizer::write([
                    'coverage' => $coverage,
                    'file' => $this->args()->get('coverage-scrutinizer')
                ]);
            }
        });
    }

    public function stop()
    {
        return Filter::on($this, __FUNCTION__, [], function($chain) {
            $this->suite()->stop();
        });
    }

    public function args()
    {
        return $this->_args;
    }

    public function suite()
    {
        return $this->_suite;
    }

    public function patchers()
    {
        return $this->_patchers;
    }

    public function reporters()
    {
        return $this->_reporters;
    }

    public function run()
    {
        $this->_start = microtime(true);
        return Filter::on($this, __FUNCTION__, [], function($chain) {

            $this->customNamespaces();

            $this->initPatchers();

            $this->patchAutoloader();

            $this->loadSpecs();

            $this->initReporters();

            $this->preProcess();

            $this->runSpecs();

            $this->postProcess();

            $this->stop();
        });
    }
}
