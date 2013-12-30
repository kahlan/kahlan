<?php
/**
 * Kahlan: PHP Testing Framework
 *
 * @copyright     Copyright 2013, CrysaLEAD
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace kahlan\reporter;

class Dot extends Terminal {

	/**
	 * Store the current number of dots.
	 *
	 * @var integer
	 */
	protected $_counter = 0;

	/**
	 * Callback called on successful spec.
	 */
	public function pass($report) {
		$this->_console('.');
	}

	/**
	 * Callback called on failure.
	 */
	public function fail($report) {
		$this->_console('F', "red");
	}

	/**
	 * Callback called when an exception occur.
	 */
	public function exception($report) {
		$this->_console('E', "magenta");
	}

	/**
	 * Callback called on a skipped spec.
	 */
	public function skip($report) {
		$this->_console('S', "cyan");
	}

	/**
	 * Callback called when a `kahlan\IncompleteException` occur.
	 */
	public function incomplete($report) {
		$this->_console('I', "yellow");
	}

	/**
	 * Callback called at the end of specs processing.
	 */
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