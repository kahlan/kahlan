<?php
/**
 * Kahlan: PHP Testing Framework
 *
 * @copyright     Copyright 2013, CrysaLEAD & Craftsmen
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace kahlan\reporter\coverage;

class Metric {

	protected $_parent = null;

	protected $_name = '';

	protected $_type = 'namespace';

	protected $_metrics = [
		'loc' => 0,
		'ncloc' => 0,
		'covered' => 0,
		'eloc' => 0,
		'methods' => 0,
		'coveredMethods' => 0
	];

	protected $_childs = [];

	public function __construct($options = []) {
		$defaults = ['name' => '', 'type' => 'namespace', 'parent' => null];
		$options += $defaults;

		$this->_parent = $options['parent'];
		$this->_type = $options['type'];

		if (!$this->_parent) {
			$this->_name = $options['name'];
			return;
		}

		$pname =  $this->_parent->name();
		switch ($this->_type) {
			case 'namespace':
			case 'function':
			case 'class':
				$this->_name = $pname ? $pname . '\\' . $options['name'] : $options['name'];
			break;
			case 'method':
				$this->_name = $pname ? $pname . '::' . $options['name'] : $options['name'];
			break;
		}
	}

	public function parent() {
		return $this->_parent;
	}

	public function name() {
		return $this->_name;
	}

	public function type() {
		return $this->_type;
	}

	public function add($name, $metrics) {
		if (!$name) {
			return $this->_merge($metrics);
		}
		list($name, $subname, $type) = $this->_parseName($name);

		if (!isset($this->_childs[$name])) {
			$parent = $this;
			$this->_childs[$name] = new Metric(compact('name', 'type', 'parent'));
		}
		$this->_merge($metrics);
		$this->_childs[$name]->add($subname, $metrics);
	}

	public function get($name = null) {
		if (!$name) {
			return $this->_metrics;
		}
		list($name, $subname, $type) = $this->_parseName($name);

		if (!isset($this->_childs[$name])) {
			return null;
		}
		return $this->_childs[$name]->get($subname);
	}

	public function childs($name = null) {
		if (!$name) {
			return $this->_childs;
		}
		list($name, $subname, $type) = $this->_parseName($name);

		if (!isset($this->_childs[$name])) {
			return null;
		}
		return $this->_childs[$name]->childs($subname);
	}

	protected function _parseName($name) {
		$subname = null;
		if (strpos($name, '\\') !== false) {
			$type = 'namespace';
			list($name, $subname) = explode('\\', $name, 2);
		} elseif (strpos($name, '::') !== false) {
			$type = 'class';
			list($name, $subname) = explode('::', $name, 2);
		}
		if (!$subname) {
			$type = $this->_type === 'class' ? 'method' : 'function';
		}
		return [$name, $subname, $type];
	}

	protected function _merge($metrics) {
		foreach (['loc', 'ncloc', 'covered', 'eloc', 'methods', 'coveredMethods'] as $name) {
			if (!isset($metrics[$name])) {
				continue;
			}
			$this->_metrics[$name] += $metrics[$name];
		}
		if ($this->_metrics['eloc']) {
			$this->_metrics['percent'] = ($this->_metrics['covered'] * 100) / $this->_metrics['eloc'];
		} else {
			$this->_metrics['percent'] = 0;
		}
	}
}

?>