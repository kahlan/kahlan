<?php
/**
 * Kahlan: PHP Testing Framework
 *
 * @copyright     Copyright 2013, CrysaLEAD
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace kahlan\reporter;

class Reporter {

	/**
	 * Total of items to reach
	 *
	 * var integer
	 */
	protected $_total = 0;

	/**
	 * Current position.
	 *
	 * var integer
	 */
	protected $_current = 0;

	public function __construct($options = []) {
	}

	public function begin($params) {
		$this->_total = max(1, $params['total']);
	}

	public function before() {
	}

	public function after() {
	}

	public function progress() {
		$this->_current++;
	}

	public function pass($report) {
	}

	public function fail($report) {
	}

	public function exception($report) {
	}

	public function skip($report) {
	}

	public function incomplete($report) {
	}

	public function end($results) {
	}
}

?>