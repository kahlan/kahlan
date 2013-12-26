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
use kahlan\Suite;
use kahlan\cli\GetOpt;
use kahlan\jit\Interceptor;
use kahlan\jit\Patchers;
use kahlan\jit\patcher\Substitute;
use kahlan\jit\patcher\Watcher;
use kahlan\jit\patcher\Monkey;
use kahlan\Reporters;
use kahlan\reporter\Dot;
use kahlan\reporter\Bar;
use kahlan\reporter\Coverage;
use kahlan\reporter\coverage\driver\Xdebug;
use kahlan\reporter\coverage\exporter\Scrutinizer;
use kahlan\filter\Filtering;

class Kahlan {

	use Filtering;

	protected $_suite = null;

	protected $_autoloader = null;

	protected $_patchers = null;

	protected $_reporters = null;

	protected $_specNamespaces = [];

	protected $_args = [
		'config' => null,
		'src' => 'src',
		'spec' => 'spec',
		'interceptor-include' => [],
		'interceptor-exclude' => [],
		'coverage' => null,
		'coverage-scrutinizer' => null,
		'autoclear' => [
			'kahlan\plugin\Monkey',
			'kahlan\plugin\Call',
			'kahlan\plugin\Stub'
		],
		'reporter' => 'dot'
	];

	public function __construct($options = []) {
		$defaults = ['autoloader' => null];
		$options += $defaults;

		$this->_suite = $suite = new Suite();
		$this->_patchers = new Patchers();
		$this->_reporters = new Reporters();
		$this->_autoloader = $options['autoloader'];
		Box::share('kahlan.suite', function() use ($suite) { return $suite; });
	}

	public function loadConfig($argv = []) {
		$args = GetOpt::parse($argv);
		if (!empty($args['config'])) {
			require $args['config'];
		} elseif (file_exists('kahlan-config.php')) {
			require 'kahlan-config.php';
		}
		$this->_args = $args + $this->_args;
	}

	public function autoloaderAdd($paths) {
		if (!$this->_autoloader || !method_exists($this->_autoloader, 'add')) {
			return;
		}
		$paths = (array) $paths;
		foreach ($paths as $path) {
			$path = realpath($path);
			$this->_specNamespaces[] = $namespace = basename($path) . '\\';
			$this->_autoloader->add($namespace, dirname($path));
		}
	}

	public function initPatchers() {
		return $this->_filter(__FUNCTION__, [], function($chain) {
			$patchers = $this->patchers();
			if ($this->_specNamespaces) {
				$patchers->add('substitute', new Substitute([
					'namespaces' => $this->_specNamespaces
				]));
			}
			$patchers->add('watcher', new Watcher());
			$patchers->add('monkey', new Monkey());
			return $patchers;
		});
	}

	public function patchAutoloader() {
		return $this->_filter(__FUNCTION__, [], function($chain) {
			Interceptor::patch([
				'loader' => [$this->_autoloader, 'loadClass'],
				'patchers' => $this->patchers(),
				'include' => $this->args('interceptor-include'),
				'exclude' => $this->args('interceptor-exclude')
			]);
		});
	}

	public function loadSpecs() {
		return $this->_filter(__FUNCTION__, [], function($chain) {
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

	public function initReporters() {
		return $this->_filter(__FUNCTION__, [], function($chain) {
			$reporters = $this->reporters();
			$reporter = $this->getConsoleReporter();
			if ($reporter) {
				$reporters->add('console', $reporter);
			}
			if ($this->args('coverage') === null) {
				return $reporters;
			}
			$coverage = new Coverage([
				'verbosity' => $this->args('coverage'),
				'driver' => new Xdebug(),
				'path' => $this->args('src')
			]);
			$reporters->add('coverage', $coverage);
			return $reporters;
		});
	}

	public function getConsoleReporter() {
		return $this->_filter(__FUNCTION__, [], function($chain) {
			if ($this->args('reporter') === 'dot') {
				return new Dot();
			}
			if ($this->args('reporter') === 'bar') {
				return new Bar();
			}
		});
	}

	public function runSpecs() {
		return $this->_filter(__FUNCTION__, [], function($chain) {
			$this->suite()->run([
				'reporters' => $this->reporters(),
				'autoclear' => $this->args('autoclear')
			]);
		});
	}

	public function postProcess() {
		return $this->_filter(__FUNCTION__, [], function($chain) {
			$coverage = $this->reporters()->get('coverage');
			if ($coverage && $this->args('coverage-scrutinizer')) {
				Scrutinizer::write([
					'coverage' => $coverage,
					'file' => $this->args('coverage-scrutinizer')
				]);
			}
		});
	}

	public function stop() {
		return $this->_filter(__FUNCTION__, [], function($chain) {
			$this->suite()->stop();
		});
	}

	public function args($key = null, $value = null) {
		if ($key === null) {
			return $this->_args;
		}
		if ($value === null) {
			return isset($this->_args[$key]) ? $this->_args[$key] : null;
		}
		$this->_args[$key] = $value;
	}

	public function suite() {
		return $this->_suite;
	}

	public function patchers() {
		return $this->_patchers;
	}

	public function reporters() {
		return $this->_reporters;
	}

	public function run() {
		return $this->_filter(__FUNCTION__, [], function($chain) {

			$this->autoloaderAdd($this->args('spec'));

			$this->initPatchers();

			$this->patchAutoloader();

			$this->loadSpecs();

			$this->initReporters();

			$this->runSpecs();

			$this->postProcess();

			$this->stop();
		});
	}
}
?>