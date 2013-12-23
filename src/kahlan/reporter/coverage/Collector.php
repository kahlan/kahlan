<?php
/**
 * Kahlan: PHP Testing Framework
 *
 * @copyright     Copyright 2013, CrysaLEAD
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace kahlan\reporter\coverage;

use dir\Dir;
use kahlan\jit\Interceptor;

class Collector {

	/**
	 * Class dependencies.
	 *
	 * @var array
	 */
	protected $_classes = [
		'parser' => 'kahlan\analysis\Parser',
	];

	protected $_driver = null;

	protected $_path = null;

	protected $_coverage = [];

	protected $_files = [];

	protected $_tree = [];

	protected $_metrics = [];

	protected $_prefix = '';

	public function __construct($options = []) {
		$defaults = [
			'driver' => null,
			'path' => [],
			'prefix' => rtrim(Interceptor::instance()->cache(), DS)
		];
		$options += $defaults;

		$this->_driver = $options['driver'];
		$this->_path = $options['path'];
		$this->_prefix = $options['prefix'];

		$files = Dir::scan([
			'path' => $this->_path,
			'include' => '*.php',
			'type' => 'file'
		]);
		foreach ($files as $file) {
			$this->_coverage[realpath($file)] = [];
		}
	}

	public function start() {
		$this->_driver->start();
	}

	public function stop() {
		$this->add($this->_driver->stop());
	}

	public function add($coverage) {
		if (!$coverage) {
			return;
		}
		$prefix = $this->_prefix;
		foreach ($coverage as $file => $data) {
			$file = preg_replace("~^{$prefix}~", '', $file);
			if (preg_match("/eval\(\)'d code$/", $file) || !isset($this->_coverage[$file])) {
				continue;
			}
			foreach ($data as $line => $value) {
				if (!isset($this->_coverage[$file][$line])) {
					$this->_coverage[$file][$line] = $value;
				} else {
					$this->_coverage[$file][$line] += $value;
				}
			}
		}
		return $this->_coverage;
	}

	public function export() {
		return $this->_coverage;
	}

	public function metrics() {
		$this->_metrics = new Metric();
		foreach ($this->_coverage as $file => $data) {
			$node = $this->_parse($file);
			$this->_processTree($file, $node, $node->tree, $data);
		}
		return $this->_metrics;
	}

	/**
	 * Helper for `Collector::export()`.
	 *
	 * @param array $nodes A node array to patch.
	 */
	protected function _processTree($file, $root, $nodes, $data, $path = '') {
		foreach ($nodes as $node) {
			if ($node->type === 'class' || $node->type === 'namespace') {
				$path = "{$path}\\" . $node->name;
				$this->_processTree($file, $root, $node->tree, $data, $path);
				continue;
			}
			if ($node->type === 'function' && !$node->isClosure) {
				$metrics = $this->_processMethod($file, $root, $node, $data);
				$prefix = $node->isMethod ? "{$path}::" : "{$path}\\";
				$this->_metrics->add(ltrim($prefix . $node->name . '()', '\\'), $metrics);
				continue;
			}
			if (!count($node->tree)) {
				continue;
			}
			$this->_processTree($file, $root, $node->tree, $data, $path);
		}
	}

	/**
	 * Helper for `Collector::export()`.
	 *
	 * @param NodeDef The node to patch.
	 */
	protected function _processMethod($file, $root, $node, $data) {
		$metrics = [
			'loc' => 0,
			'ncloc' => 0,
			'covered' => 0,
			'eloc' => 0,
			'percent' => 0,
			'methods' => 1,
			'coveredMethods' => 0
		];
		if ($node->type !== 'function') {
			continue;
		}
		for ($line = $node->lines['start']; $line <= $node->lines['stop']; $line++) {
			if ($node->lines['start'] === null) {
				continue;
			}
			if (!isset($data[$line])) {
				$metrics['ncloc']++;
			} elseif ($data[$line]) {
				$metrics['covered']++;
			}
		}
		$metrics['file'] = $file;
		$metrics['line'] = $node->lines['start'];
		$metrics['loc'] = ($node->lines['stop'] - $node->lines['start']) + 1;
		$metrics['eloc'] = $metrics['loc'] - $metrics['ncloc'];
		if ($metrics['covered']) {
			$metrics['coveredMethods'] = 1;
		}
		return $metrics;
	}

	protected function _parse($file) {
		if (isset($this->_tree[$file])) {
			return $this->_tree[$file];
		}
		$parser = $this->_classes['parser'];
		return $this->_tree[$file] = $parser::parse(file_get_contents($file), true);
	}
}

?>