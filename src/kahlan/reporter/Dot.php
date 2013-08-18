<?php
/**
 * Kahlan: PHP Testing Framework
 *
 * @copyright     Copyright 2013, CrysaLEAD & Craftsmen
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace kahlan\reporter;

class Dot extends Terminal {

	protected $_counter = 0;

	public function pass($report) {
		$this->_console('.');
	}

	public function fail($report) {
		$this->_console('F', "red");
	}

	public function skip($report) {
		$this->_console('S', "cyan");
	}

	public function incomplete($report) {
		$this->_console('I', "yellow");
	}

	public function exception($report) {
		$this->_console('E', "magenta");
	}

	public function end($results) {
		do {
			$this->_console(' ');
		} while ($this->_counter % 80 !== 0);

		$this->console("\n\n");

		foreach ($results as $type => $reports) {
			foreach ($reports as $report) {
				$this->_report($report);
			}
		}

		$this->console("\n");
		$this->_summary($results);
	}

	protected function _console($string, $options = null) {
		$this->console($string, $options);
		$this->_counter++;
		if ($this->_counter % 80 === 0) {
			$this->console(' ' . floor(($this->_current * 100) / $this->_total) . "%\n");
		}
	}
}