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
use kahlan\reporter\Coverage;
use kahlan\reporter\coverage\driver\Xdebug;
use kahlan\reporter\coverage\exporter\Scrutinizer;
use kahlan\filter\Filtering;

class Kahlan {

	use Filtering;

	protected $_autoloader = null;

	public function initPatchers($options) {
		return $this->_filter(__FUNCTION__, func_get_args(), function($chain, $options) {
			$patchers = new Patchers();
			$patchers->add('substitute', new Substitute(['namespaces' => $options['substitute']]));
			$patchers->add('watcher', new Watcher());
			$patchers->add('monkey', new Monkey());
			return $patchers;
		});
	}

	public function patchAutoloader($autoloader, $patchers, $options) {
		return $this->_filter(__FUNCTION__, func_get_args(), function($chain, $autoloader, $patchers, $options) {
			Interceptor::patch([
				'loader' => [$autoloader, 'loadClass'],
				'patchers' => $patchers,
				'include' => $options['interceptor-include'],
				'exclude' => $options['interceptor-exclude']
			]);
		});
	}

	public function loadSpecs($options) {
		return $this->_filter(__FUNCTION__, func_get_args(), function($chain, $options) {
			return Dir::scan([
				'path' => $options['spec'],
				'include' => '*Spec.php',
				'type' => 'file'
			]);
		});
	}

	public function initReporters($options) {
		return $this->_filter(__FUNCTION__, func_get_args(), function($chain, $options) {
			$reporters = new Reporters();
			$reporters->add('console', new Dot());

			if(!isset($options['coverage'])) {
				return $reporters;
			}
			$coverage = new Coverage([
				'verbosity' => $options['coverage'], 'driver' => new Xdebug(), 'path' => $options['src']
			]);
			$reporters->add('coverage', $coverage);
			return $reporters;
		});
	}

	public function runSpecs($suite, $reporters, $options) {
		return $this->_filter(__FUNCTION__, func_get_args(), function($chain, $suite, $reporters, $options) {
			$suite->run([
				'reporters' => $reporters,
				'autoclear' => $options['autoclear']
			]);
		});
	}

	public function postProcess($suite, $reporters, $options) {
		return $this->_filter(__FUNCTION__, func_get_args(), function($chain, $suite, $reporters, $options) {
			$coverage = $reporters->get('coverage');
			if ($coverage && $options['coverage-scrutinizer']) {
				Scrutinizer::write([
					'coverage' => $coverage, 'file' => $options['coverage-scrutinizer']
				]);
			}
		});
	}

	public function __construct($autoloader, $argv = []) {
		$this->_autoloader = $autoloader;
		$this->_options = GetOpt::parse($argv);
		$this->_options += [
			'c' => null,
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
			'substitute' => ['spec\\']
		];
	}

	public function options($key = null, $value = null) {
		if ($key === null) {
			return $this->_options;
		}
		if ($value === null) {
			return isset($this->_options[$key]) ? $this->_options[$key] : null;
		}
		$this->_options[$key] = $value;
	}

	public function run() {
		$options = &$this->_options;

		if ($options['c']) {
			require $options['c'];
		} elseif (file_exists('kahlan-config.php')) {
			require 'kahlan-config.php';
		}

		if (is_array($options['spec'])) {
			throw new Exception("The spec directory must be unique");
		}

		$this->autoloadSpec();

		Box::share('kahlan.suite', function() { return new Suite(); });

		$suite = Box::get('kahlan.suite');

		$patchers = $this->initPatchers($options);

		$this->patchAutoloader($this->_autoloader, $patchers, $options);

		$files = $this->loadSpecs($options);

		foreach($files as $file) {
			require $file;
		}

		$reporters = $this->initReporters($options);

		$this->runSpecs($suite, $reporters, $options);

		$this->postProcess($suite, $reporters, $options);

		$suite->stop();
	}

	public function autoloadSpec() {
		if (!method_exists($this->_autoloader, 'add')) {
			return;
		}
		$path = realpath($this->_options['spec']);
		$namespace = basename($path) . '\\';
		$this->_autoloader->add($namespace, dirname($path));
	}
}
?>