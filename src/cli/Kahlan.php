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
use kahlan\cli\GetOpt;
use kahlan\jit\Interceptor;
use kahlan\jit\Patchers;
use kahlan\jit\patcher\Substitute;
use kahlan\jit\patcher\Pointcut;
use kahlan\jit\patcher\Monkey;
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

    protected $_args = [
        'config' => null,
        'src' => 'src',
        'spec' => 'spec',
        'ff' => null,
        'interceptor-include' => [],
        'interceptor-exclude' => [],
        'interceptor-persistent' => true,
        'coverage' => null,
        'coverage-scrutinizer' => null,
        'autoclear' => [
            'kahlan\plugin\Monkey',
            'kahlan\plugin\Call',
            'kahlan\plugin\Stub'
        ],
        'reporter' => 'dot'
    ];

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
        $args = GetOpt::parse($argv, [
            'coverage' => 'numeric',
            'ff'       => 'numeric'
        ]);
        if (!empty($args['config'])) {
            require $args['config'];
        } elseif (file_exists('kahlan-config.php')) {
            require 'kahlan-config.php';
        }
        $this->_args = $args + $this->_args;
    }

    public function customNamespaces()
    {
        return Filter::on($this, __FUNCTION__, [], function($chain) {
            if (!$this->_autoloader || !method_exists($this->_autoloader, 'add')) {
                echo Cli::color("The defined autoloader doesn't support `add()` calls\n", 'yellow');
                return;
            }
            $paths = (array) $this->args('spec');
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
                'include' => $this->args('interceptor-include'),
                'exclude' => $this->args('interceptor-exclude'),
                'persistent' => $this->args('interceptor-persistent')
            ]);
        });
    }

    public function loadSpecs()
    {
        return Filter::on($this, __FUNCTION__, [], function($chain) {
            $files = Dir::scan([
                'path' => $this->args('spec'),
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
            if ($this->args('coverage')) {
                $this->coverageReporter();
            }
        });
    }

    public function consoleReporter()
    {
        return Filter::on($this, __FUNCTION__, [], function($chain) {
            $reporters = $this->reporters();
            $start = $this->_start;
            if ($this->args('reporter') === 'dot') {
                $reporter = new Dot(compact('start'));
            }
            if ($this->args('reporter') === 'bar') {
                $reporter = new Bar(compact('start'));
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
                'verbosity' => $this->args('coverage'),
                'driver' => new Xdebug(),
                'path' => $this->args('src')
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
                'autoclear' => $this->args('autoclear'),
                'ff'        => $this->args('ff')
            ]);
        });
    }

    public function postProcess()
    {
        return Filter::on($this, __FUNCTION__, [], function($chain) {
            $coverage = $this->reporters()->get('coverage');
            if ($coverage && $this->args('coverage-scrutinizer')) {
                Scrutinizer::write([
                    'coverage' => $coverage,
                    'file' => $this->args('coverage-scrutinizer')
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

    public function args($key = null, $value = null)
    {
        if ($key === null) {
            return $this->_args;
        }
        if ($value === null) {
            return isset($this->_args[$key]) ? $this->_args[$key] : null;
        }
        $this->_args[$key] = $value;
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
