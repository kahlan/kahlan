<?php
/**
 * Kahlan: PHP Testing Framework
 *
 * @copyright     Copyright 2013, CrysaLEAD
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace kahlan\util;

use Exception;
use kahlan\util\Set;

class String {

	public static function dump($value) {
		$type = gettype($value);
		$value = static::toString($value, ['quote' => true]);
		return "({$type}) {$value}";
	}

	public static function toString($value, $options = []) {
		$defaults = [
			'quote' => false,
			'array' => [
				'indent' => 1,
				'char' => ' ',
				'multiplier' => 4
			]
		];

		$options = Set::merge($defaults, $options);

		if (is_callable($value)) {
			return '`Closure`';
		}
		if (is_array($value)) {
			return static::_arrayToString($value, $options);
		}
		if (is_bool($value)) {
			return $value ? 'true' : 'false';
		}
		if (is_null($value)) {
			return 'null';
		}
		if (is_object($value)) {
			if ($value instanceof Exception) {
				$msg = '`' . get_class($value) .'` with message \'`'. $value->getMessage(). '`\'';
				return $msg . ' in '. $value->getFile() . ':' . $value->getLine();
			}
			if (!method_exists($value, '__toString')) {
				return get_class($value) . '()';
			} else {
				return (string) $value;
			}
		}
		if (!$options['quote'] || !is_string($value)) {
			return (string) $value;
		}
		return '"' . static::expands($value) . '"';
	}

	protected static function _arrayToString($datas, $options) {
		if (!count($datas)) {
			return '[]';
		}

		extract($options['array']);
		$comma = false;

		$tab = str_repeat($char, $indent * $multiplier);

		$string = "[\n";

		foreach($datas as $key => $value) {
			if ($comma) {
				$string .= ",\n";
			}
			$comma = true;
			$string .= $tab . $key . ' => ';
			if (is_array($value)) {
				$options['array']['indent'] = $indent + 1;
				$string .= static::_arrayToString($value, $options);
			} else {
				$string .= static::toString($value, $options);
			}
		}
		$tab = str_repeat($char, ($indent - 1) * $multiplier);
		return $string . "\n" . $tab . "]";
	}

	/**
	 * Expands escape sequences and escape special chars.
	 *
	 * @param  string $string A string which contain escape sequence.
	 * @return string A valid double quoted string.
	 */
	public static function expands($string) {
		$es = ['0', 'a', 'b', 't', 'n', 'v', 'f', 'r'];
		$unescaped = '';
		$chars = str_split($string);
		foreach ($chars as $char) {
			if (!$char) {
				continue;
			}
			$value = ord($char);
			if ($value >= 7 && $value <= 13) {
				$value -= 6;
			}
			if ($value <= 7) {
				$unescaped .= '\\' . $es[$value];
			} elseif ($char === '"' || $char === '$') {
				$unescaped .= '\\' . $char;
			} else {
				$unescaped .= $char;
			}
		}
		return $unescaped;
	}

	/**
	 * Replaces variable placeholders inside a string with any given data. Each key
	 * in the `$data` array corresponds to a variable placeholder name in `$str`.
	 *
	 * Usage:
	 * {{{
	 * String::insert(
	 *     'My name is {:name} and I am {:age} years old.', ['name' => 'Bob', 'age' => '65']
	 * );
	 * }}}
	 *
	 * @param  string $str A string containing variable place-holders.
	 * @param  array  $data A key, value array where each key stands for a place-holder variable
	 *                name to be replaced with value.
	 * @param  array  $options Available options are:
	 *                - `'before'`: The character or string in front of the name of the variable
	 *                 place-holder (defaults to `'{:'`).
	 *                - `'after'`: The character or string after the name of the variable
	 *                 place-holder (defaults to `}`).
	 *                - `'clean'`: A boolean or array with instructions for `String::clean()`.
	 * @return string
	 */
	public static function insert($str, array $data, array $options = array()) {
		$options += ['before' => '{:', 'after' => '}', 'clean' => false];

		$replace = [];
		foreach ($data as $key => $val) {
			$val = (is_array($val) || is_resource($val) || $val instanceof Closure) ? '' : $val;
			if (is_object($val) && !method_exists($val, '__toString')) {
				$val = '';
			}
			$val = (string) $val;
			$replace["{$options['before']}{$key}{$options['after']}"] = $val;
		}

		$str = strtr($str, $replace);
		return $options['clean'] ? static::clean($str, $options) : $str;
	}

	/**
	 * Cleans up a `String::insert()` formatted string with given `$options` depending
	 * on the `'clean'` option. The goal of this function is to replace all whitespace
	 * and unneeded mark-up around place-holders that did not get replaced by `String::insert()`.
	 *
	 * @param  string $str The string to clean.
	 * @param  array  $options Available options are:
 	 *                - `'before'`: characters marking the start of targeted substring.
	 *                - `'after'`: characters marking the end of targeted substring.
	 *                - `'gap'`: Regular expression matching gaps.
	 *                - `'word'`: Regular expression matching words.
	 * @return string The cleaned string.
	 */
	public static function clean($str, array $options = array()) {
		$options += [
			'before' => '{:',
			'after' => '}',
			'word' => '[\w,.]+',
			'gap' => '\s*(?:(?:and|or|,)\s*)?'
		];

		extract($options);

		$callback = function($matches) {
			if (preg_match('/^(\s+).*?(\s+)$/', $matches[0], $spaces)) {
				return strlen($spaces[1]) >= strlen($spaces[2]) ? $spaces[1] : $spaces[2];
			}
			return '';
		};
		$pattern = $gap. preg_quote($before) . $word . preg_quote($after) . $gap;
		return preg_replace_callback('/(' . $pattern .')+/', $callback, $str);
	}

	/**
	 * Hash helper
	 *
	 * @param  mixed  $reference An instance or a fully namespaced class name.
	 * @return string A stirng hash.
	 * @throws InvalidArgumentException
	 */
	public static function hash($reference) {
		if (is_object($reference)) {
			return spl_object_hash($reference);
		}
		if (is_string($reference)) {
			return $reference;
		}
		throw new InvalidArgumentException("Invalid reference.");
	}
}

?>