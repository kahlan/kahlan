<?php
/**
 * Kahlan: PHP Testing Framework
 *
 * @copyright     Copyright 2013, CrysaLEAD
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace kahlan\cli;

class GetOpt {

	public static function parse($argv) {
		$args = array();
		foreach($argv as $arg) {
			if ($arg === '--') {
				break;
			}
			if ($arg[0] === '-') {
				list($key, $value) = static::_parseOpt(ltrim($arg,'-'));
				$args = static::_mergeOpt($args, $key, $value);
			}
		}
		return $args;
	}

	protected static function _parseOpt($arg) {
		$pos = strpos($arg, '=');
		if ($pos === false) {
			return [$arg, false];
		}
		return [substr($arg, 0, $pos), substr($arg, $pos + 1)];
	}

	protected static function _mergeOpt($args, $key, $value) {
		if (!isset($args[$key])) {
			$args[$key] = $value;
		} else {
			$args[$key] = (array) $args[$key];
			$args[$key][] = $value;
		}
		return $args;
	}
}

?>