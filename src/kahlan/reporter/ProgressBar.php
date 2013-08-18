<?php
/**
 * Kahlan: PHP Testing Framework
 *
 * @copyright     Copyright 2013, CrysaLEAD & Craftsmen
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace kahlan\reporter;

use kahlan\util\Set;
use kahlan\util\String;

class ProgressBar extends Terminal {

	/**
	 * Colors preference.
	 *
	 * var array
	 */
	protected $_colors = [];

	/**
	 * Format of the progress bar.
	 *
	 * var string
	 */
	protected $_format = '';

	/**
	 * Chars preference.
	 *
	 * var array
	 */
	protected $_chars = [];

	/**
	 * Progress bar color.
	 *
	 * var integer
	 */
	protected $_color = 37;

	/**
	 * Size of the progress bar.
	 *
	 * var integer
	 */
	protected $_size = 0;

	/**
	 * Constructor
	 *
	 * @param array $options Options array
	 */
	public function __construct($options = []) {
		parent::__construct($options);
		$defaults = [
			'size' => 50,
			'colors' => [
				'failure' => 'red',
				'success' => 'green',
				'incomplete' => 'yellow'
			],
			'chars' => [
				'bar' => '=',
				'indicator' => '>'
			],
			'format' => '[{:b}{:i}] {:p}%'
		];
		$options = Set::merge($defaults, $options);

		foreach ($options as $key => $value) {
			$_key = "_{$key}";
			$this->$_key = $value;
		}
		$this->_color = $this->_colors['success'];
	}

	public function progress() {
		parent::progress();
		$this->_progressBar();
	}

	public function fail($report) {
		$this->_color = $this->_colors['failure'];
		$this->console("\n");
		$this->_report($report);
	}

	public function incomplete($report) {
		$this->_color = $this->_colors['incomplete'];
		$this->console("\n");
		$this->_report($report);
	}

	public function exception($report) {
		$this->_color = $this->_colors['failure'];
		$this->console("\n");
		$this->_report($report);
	}

	protected function _progressBar() {
		if ($this->_current > $this->_total) {
			return;
		}

		$percent = $this->_current / $this->_total;
		$nb = $percent * $this->_size;

		$b = str_repeat($this->_chars['bar'], floor($nb));
		$i = '';

		if ($nb < $this->_size) {
			$i = str_pad($this->_chars['indicator'], $this->_size - strlen($b));
		}

		$p = floor($percent * 100);

		$string = String::insert($this->_format, compact('p', 'b', 'i'));

		$string .= ($this->_current === $this->_total ? "\n" : '');
		$this->console("\r" . $string, 'n;' . $this->_color);
	}

	public function end($results) {
		$this->console("\n");
		$this->_summary($results);
	}
}

?>