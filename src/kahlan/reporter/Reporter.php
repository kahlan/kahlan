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
	 * @var integer
	 */
	protected $_total = 0;

	/**
	 * Current position.
	 *
	 * @var integer
	 */
	protected $_current = 0;

	/**
	 * Reporter constructor
	 *
	 * @param array $options (Unused).
	 */
	public function __construct($options = []) {
	}

	/**
	 * Callback called before any specs processing.
	 *
	 * @param array $params The suite params array.
	 */
	public function begin($params) {
		$this->_total = max(1, $params['total']);
	}

	/**
	 * Callback called before a spec.
	 */
	public function before() {
	}

	/**
	 * Callback called after a spec.
	 */
	public function after() {
	}

	/**
	 * Callback called when a new spec file is processed.
	 */
	public function progress() {
		$this->_current++;
	}

	/**
	 * Callback called on successful spec.
	 */
	public function pass($report) {
	}

	/**
	 * Callback called on failure.
	 */
	public function fail($report) {
	}

	/**
	 * Callback called when an exception occur.
	 */
	public function exception($report) {
	}

	/**
	 * Callback called on a skipped spec.
	 */
	public function skip($report) {
	}

	/**
	 * Callback called when a `kahlan\IncompleteException` occur.
	 */
	public function incomplete($report) {
	}

	/**
	 * Callback called at the end of specs processing.
	 */
	public function end($results) {
	}
}

?>