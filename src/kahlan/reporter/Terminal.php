<?php
/**
 * Kahlan: PHP Testing Framework
 *
 * @copyright     Copyright 2013, CrysaLEAD
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace kahlan\reporter;

use kahlan\cli\Cli;
use kahlan\util\String;
use kahlan\analysis\Debugger;

class Terminal extends Reporter {

	/**
	 * Output stream, STDOUT
	 *
	 * @var stream
	 */
	protected $_output = null;

	/**
	 * Reporter constructor
	 *
	 * @param array $options.
	 */
	public function __construct($options = []) {
		parent::__construct($options);
		$defaults = ['output' => fopen('php://stdout', 'r')];
		$options += $defaults;
		$this->_output = $options['output'];
	}

	/**
	 * Print a string to STDOUT.
	 *
	 * @param mixed        $string  The string to print.
	 * @param string|array $options The possible values for an array are:
	 *                     - `'style`: a style code.
	 *                     - `'color'`: a color code.
	 *                     - `'background'`: a background color code.
	 *                     The string must respect one of the following format:
	 *                     - `'style;color;background'`
	 *                     - `'style;color'`
	 *                     - `'color'`
	 *
	 */
	public function console($string, $options = null) {
		fwrite($this->_output, Cli::color($string, $options));
	}

	/**
	 * Callback called before any specs processing.
	 *
	 * @param array $params The suite params array.
	 */
	public function begin($params) {
		parent::begin($params);
		$this->console("-");
		$this->console("Kahlan - PHP Testing Framework\n" , 'green');
		$this->console("\nWorking Directory: ", 'blue');
		$this->console(getcwd() . "\n\n");
	}

	/**
	 * Print a report to STDOUT
	 *
	 * @param array $report A report array.
	 */
	protected function _report($report) {
		switch($report['type']) {
			case 'fail':
				$this->_reportFailure($report);
			break;
			case 'incomplete':
				$this->_reportIncomplete($report);
			break;
			case 'exception':
				$this->_reportException($report);
			break;
		}
	}

	/**
	 * Print a failure report to STDOUT
	 *
	 * @param array $report A report array.
	 */
	protected function _reportFailure($report) {
		$this->console("[Failure] ", "n;red");
		$this->_messages($report['messages']);
		$this->_reportDescription($report);
		$this->console("Trace: ", "n;yellow");
		$this->console(Debugger::trace([
			'trace' => $report['exception'], 'depth' => 1
		]));
		$this->console("\n\n");
	}

	/**
	 * Report a description of a spec
	 *
	 * @param array $report A report array.
	 */
	protected function _reportDescription($report) {
		$not = $report['not'];
		$description = $report['description'];
		if (is_array($description)) {
			$params = $description['params'];
			$description = $description['description'];
		} else {
			$params = $report['params'];
		}
		foreach ($params as $key => $value) {
			$this->console("{$key}: ", 'n;yellow');
			$this->console(String::dump($value) . "\n");
		}
		$this->console("Description:", "n;magenta");
		$this->console(" {$report['matcher']} expected actual to ");
		if ($not) {
			$this->console("NOT ", 'n;magenta');
		}
		$this->console("{$description}\n");
	}

	/**
	 * Print an incomplete exception report to STDOUT
	 *
	 * @param array $report A report array.
	 */
	protected function _reportIncomplete($report) {
		$this->console("[Incomplete test] ", "n;yellow");
		$this->_messages($report['messages']);
		$this->console("Description:", "n;magenta");
		$this->console(" You are using an unexisting class.\n");
		$this->console("Trace: ", "n;yellow");
		$this->console(Debugger::trace([
			'trace' => $report['exception'], 'start' => 1, 'depth' => 1
		]));
		$this->console("\n\n");
	}

	/**
	 * Print an exception report to STDOUT
	 *
	 * @param array $report A report array.
	 */
	protected function _reportException($report) {
		$this->console("[Uncatched Exception] ", "n;magenta");
		$this->_messages($report['messages']);
		$this->console("Trace:\n", "n;yellow");
		$this->console(Debugger::trace(['trace' => $report['exception']]));
		$this->console("\n\n");
	}

	/**
	 * Print an array of description messages to STDOUT
	 *
	 * @param array $messages An array of description message.
	 */
	protected function _messages($messages) {
		$tab = 0;
		foreach ($messages as $message) {
			$this->console(str_repeat("    ", $tab));
			preg_match('/^((?:it|when)?\s*(?:not)?)(.*)$/', $message, $matches);
			$this->console($matches[1], "n;magenta");
			$this->console($matches[2]);
			$this->console("\n");
			$tab++;
		}
		$this->console("\n");
	}

	/**
	 * Print a summary of specs execution to STDOUT
	 *
	 * @param array $results The results array of the execution.
	 */
	public function _summary($results) {

		$passed = count($results['pass']) + count($results['skip']);
		$failed = 0;
		foreach (['exception', 'incomplete', 'fail'] as $value) {
			${$value} = count($results[$value]);
			$failed += ${$value};
		}
		$total = $passed + $failed;

		$this->console('Executed ' . $passed . " of {$total} ");

		if ($failed) {
			$this->console("FAIL ", "red");
			$this->console("(");
			$comma = false;
			if ($fail) {
				$this->console("FAILURE: " . $fail , "red");
				$comma = true;
			}
			if ($incomplete) {
				if ($comma) {
					$this->console(", ");
				}
				$this->console("INCOMPLETE: " . $incomplete , "yellow");
				$comma = true;
			}
			if ($exception) {
				if ($comma) {
					$this->console(", ");
				}
				$this->console("EXCEPTION: " . $exception , "magenta");
			}
			$this->console(")");
		} else {
			$this->console("PASS", "green");
		}
		$time = number_format(microtime(true) - $this->_start, 3);
		$this->console(" in {$time} seconds\n\n\n");
	}

	/**
	 * Destructor
	 */
	public function __destruct() {
		if ($this->_output) {
			fclose($this->_output);
		}
	}
}

?>