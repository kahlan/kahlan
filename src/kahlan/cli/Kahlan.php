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
use kahlan\Reporters;
use kahlan\reporter\Dot;
use kahlan\reporter\Bar;
use kahlan\reporter\Coverage;
use kahlan\reporter\coverage\driver\Xdebug;
use kahlan\reporter\coverage\exporter\Scrutinizer;

class Kahlan {

	use Filterable;

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

	public function __construct($options = []) {
		$defaults = ['autoloader' => null];
		$options += $defaults;

		$this->_suite = $suite = new Suite();
		$this->_patchers = new Patchers();
		$this->_reporters = new Reporters();
		$this->_autoloader = $options['autoloader'];
		Box::service('kahlan.suite', function() use ($suite) { return $suite; });
	}

	public function loadConfig($argv = []) {
		$args = GetOpt::parse($argv, [
			'coverage' => 'numeric'
		]);
		if (!empty($args['config'])) {
			require $args['config'];
		} elseif (file_exists('kahlan-config.php')) {
			require 'kahlan-config.php';
		}
		$this->_args = $args + $this->_args;
		$this->_args['coverage'] = $this->_args['coverage'];
	}

	public function customNamespaces() {
		return Filter::on($this, __FUNCTION__, [], function($chain) {
			if (!$this->_autoloader || !method_exists($this->_autoloader, 'add')) {
				echo Cli::color("The defined autoloader doesn't support `add()` calls\n", 'yellow');
				return;
			}
			$paths = (array) $this->args('spec');
			foreach ($paths as $path) {
				$path = realpath($path);
				$this->_specNamespaces[] = $namespace = basename($path) . '\\';
				$this->_autoloader->add($namespace, dirname($path));
			}
		});
	}

	public function initPatchers() {
		return Filter::on($this, __FUNCTION__, [], function($chain) {
			$patchers = $this->patchers();
			if ($this->_specNamespaces) {
				$patchers->add('substitute', new Substitute([
					'namespaces' => $this->_specNamespaces
				]));
			}
			$patchers->add('pointcut', new Pointcut());
			$patchers->add('monkey', new Monkey());
			return $patchers;
		});
	}

	public function patchAutoloader() {
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

	public function loadSpecs() {
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

	public function initReporters() {
		return Filter::on($this, __FUNCTION__, [], function($chain) {
			$reporters = $this->reporters();
			$reporter = $this->getConsoleReporter();
			if ($reporter) {
				$reporters->add('console', $reporter);
			}
			if (!$this->args('coverage')) {
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
		return Filter::on($this, __FUNCTION__, [], function($chain) {
			if ($this->args('reporter') === 'dot') {
				return new Dot();
			}
			if ($this->args('reporter') === 'bar') {
				return new Bar();
			}
		});
	}

	public function runSpecs() {
		return Filter::on($this, __FUNCTION__, [], function($chain) {
			$this->suite()->run([
				'reporters' => $this->reporters(),
				'autoclear' => $this->args('autoclear')
			]);
		});
	}

	public function postProcess() {
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

	public function stop() {
		return Filter::on($this, __FUNCTION__, [], function($chain) {
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
		return Filter::on($this, __FUNCTION__, [], function($chain) {

			$this->customNamespaces();

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