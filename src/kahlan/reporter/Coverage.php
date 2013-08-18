<?php
/**
 * Kahlan: PHP Testing Framework
 *
 * @copyright     Copyright 2013, CrysaLEAD & Craftsmen
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace kahlan\reporter;

use kahlan\reporter\coverage\Collector;

class Coverage extends Terminal {

	protected $_verbosity = 0;

	protected $_collector = '';

	/**
	 * Display coverage results in the console.
	 *
	 * @param array $options The options for the reporter, the options are:
	 *              - `'verbosity`' : The verbosity level:
	 *                  - 0 : overall coverage value for the whole code.
	 *                  - 1 : coverage for namespaces.
	 *                  - 2 : coverage for namespaces and classes.
	 *                  - 3 : coverage for namespaces, classes, methods and functions.
	 */

	public function __construct($options = []) {
		parent::__construct($options);
		$defaults = ['verbosity' => 0];
		$options += $defaults;
		$this->_verbosity = (int) $options['verbosity'];
		$this->_collector = new Collector($options);
	}

	public function begin($params) {

	}

	public function before() {
		$this->_collector->start();
	}

	public function after() {
		$this->_collector->stop();
	}

	public function export() {
		return $this->_collector->export();
	}

	public function metrics() {
		return $this->_collector->metrics();
	}

	public function end($results) {
		$metrics = $this->metrics();
		$this->console("\nCoverage Summary\n----------------\n\n");

		$stats = $metrics->get();
		$percent = number_format($stats['percent'], 2);
		$style = $this->_style($percent);
		$this->console(str_pad("Lines: {$percent}%", 15), $style);
		$this->console("({$stats['covered']}/{$stats['eloc']})");
		$this->console("\n");
		if (!$this->_verbosity) {
			return;
		}
		$this->_render($metrics->childs());
	}

	protected function _render($metrics) {

		foreach ($metrics as $child) {
			$type = $child->type();
			if ($this->_verbosity === 1 && ($type === 'class' || $type === 'function')) {
				continue;
			}
			if ($this->_verbosity === 2 && ($type === 'function' || $type === 'method')) {
				continue;
			}
			$name = $child->name();
			$stats = $child->get();
			$percent = number_format($stats['percent'], 2);
			$style = $this->_style($percent);
			$this->console(str_pad("Lines: {$percent}%", 15), $style);
			$this->console(str_pad("({$stats['covered']}/{$stats['eloc']})", 20));
			$this->console("{$name}\n");
			$this->_render($child->childs());
		}
	}

	protected function _style($percent) {
		switch(true) {
			case $percent >= 80:
				return 'n;green';
			break;
			case $percent >= 60:
				return 'n;default';
			break;
			case $percent >= 40:
				return 'n;yellow';
			break;
		}
		return 'n;red';
	}
}

?>