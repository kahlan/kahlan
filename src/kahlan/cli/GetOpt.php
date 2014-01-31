<?php
/**
 * Kahlan: PHP Testing Framework
 *
 * @copyright     Copyright 2013, CrysaLEAD
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace kahlan\cli;

class GetOpt {

	public static function parse($argv, $types = []) {
		$args = array();
		foreach($argv as $arg) {
			if ($arg === '--') {
				break;
			}
			if ($arg[0] === '-') {
				list($key, $value) = static::_parseOpt(ltrim($arg,'-'), $types);
				$args = static::_mergeOpt($args, $key, $value);
			}
		}
		return $args;
	}

	protected static function _parseOpt($arg, $types) {
		$pos = strpos($arg, '=');
		if ($pos === false) {
			return static::_cast([$arg, ''], $types);
		}
		return static::_cast([substr($arg, 0, $pos), substr($arg, $pos + 1)], $types);
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

	protected static function _cast($arg, $types) {
		if (!isset($types[$arg[0]])) {
			return $arg;
		}
		$type = $types[$arg[0]];
		if ($type === 'bool') {
			$arg[1] = $arg[1] === 'false'? false : true;
		} elseif ($type === 'numeric') {
			$arg[1] = $arg[1] !== '' ? $arg[1] + 0 : 1;
		}
		return $arg;
	}
}

?>