<?php
/**
 * Kahlan: PHP Testing Framework
 *
 * @copyright     Copyright 2013, CrysaLEAD
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace kahlan\cli;

use box\Box;
use dir\Dir;
use kahlan\Suite;
use kahlan\cli\GetOpt;
use kahlan\jit\Interceptor;
use kahlan\jit\Patcher;
use kahlan\jit\patcher\Substitute;
use kahlan\jit\patcher\Watcher;
use kahlan\jit\patcher\Monkey;
use kahlan\Reporter;
use kahlan\reporter\Dot;
use kahlan\reporter\Coverage;
use kahlan\reporter\coverage\driver\Xdebug;
use kahlan\reporter\coverage\exporter\Scrutinizer;
use kahlan\filter\Filtering;

class Kahlan {

	use Filtering;

	public function initPatchers($options) {
		return $this->_filter(__FUNCTION__, func_get_args(), function($chain, $options) {
			$patcher = new Patcher();
			$patcher->add('substitute', new Substitute(['namespaces' => $options['substitute']]));
			$patcher->add('watcher', new Watcher());
			$patcher->add('monkey', new Monkey());
			return $patcher;
		});
	}

	public function patchAutoloader($autoloader, $patcher, $options) {
		return $this->_filter(__FUNCTION__, func_get_args(), function($chain, $autoloader, $patcher, $options) {
			Interceptor::patch([
				'loader' => [$autoloader, 'loadClass'],
				'patcher' => $patcher,
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
			$reporter = new Reporter();
			$reporter->add('console', new Dot());

			if(!isset($options['coverage'])) {
				return $reporter;
			}
			$coverage = new Coverage([
				'verbosity' => $options['coverage'], 'driver' => new Xdebug(), 'path' => $options['src']
			]);
			$reporter->add('coverage', $coverage);
			return $reporter;
		});
	}

	public function runSpecs($suite, $reporter, $options) {
		return $this->_filter(__FUNCTION__, func_get_args(), function($chain, $suite, $reporter, $options) {
			$suite->run([
				'reporter' => $reporter,
				'autoclear' => $options['autoclear']
			]);
		});
	}

	public function postProcess($suite, $reporter, $options) {
		return $this->_filter(__FUNCTION__, func_get_args(), function($chain, $suite, $reporter, $options) {
			$coverage = $reporter->get('coverage');
			if ($coverage && $options['coverage-scrutinizer']) {
				Scrutinizer::write([
					'coverage' => $coverage, 'file' => $options['coverage-scrutinizer']
				]);
			}
		});
	}

	public function run($autoloader, $argv = []) {
		$options = GetOpt::parse($argv);
		$options += [
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

		if ($options['c']) {
			require $options['c'];
		} elseif (file_exists('kahlan-config.php')) {
			require 'kahlan-config.php';
		}

		Box::share('kahlan.suite', function() { return new Suite(); });
		$suite = Box::get('kahlan.suite');

		$patcher = $this->initPatchers($options);

		$this->patchAutoloader($autoloader, $patcher, $options);

		$files = $this->loadSpecs($options);

		foreach($files as $file) {
			require $file;
		}

		$reporter = $this->initReporters($options);

		$this->runSpecs($suite, $reporter, $options);

		$this->postProcess($suite, $reporter, $options);

		$suite->stop();
	}
}
?>