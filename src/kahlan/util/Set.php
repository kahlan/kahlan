<?php
/**
 * Kahlan: PHP Testing Framework
 *
 * @copyright     Copyright 2013, Union of RAD (http://union-of-rad.org), CrysaLEAD & Craftsmen
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace kahlan\util;

use Exception;
use BadFunctionCallException;

class Set {

	/**
	 * Determines if the array elements in `$array2` are wholly contained within `$array1`. Works
	 * recursively.
	 *
	 * @param  array   $array1 First value.
	 * @param  array   $array2 Second value.
	 * @return boolean Returns `true` if `$array1` wholly contains the keys and values of `$array2`,
	 *                  otherwise, returns `false`. Returns `false` if either array is empty.
	 */
	public static function contains($array1, $array2) {
		if (!$array1 || !$array2) {
			return false;
		}
		foreach ($array2 as $key => $val) {
			if (!isset($array1[$key]) || $array1[$key] !== $val) {
				return false;
			}
			if (is_array($val) && !static::contains($array1[$key], $val)) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Counts the dimensions of an array. If `$all` is set to `false` (which is the default) it will
	 * only consider the dimension of the first element in the array.
	 *
	 * @param  array   $data Array to count dimensions on.
	 * @param  array   $options
	 * @return integer The number of dimensions in `$array`.
	 */
	public static function depth($data, $options = []) {
		$defaults = ['all' => false, 'count' => 0];
		$options += $defaults;

		if (!$data) {
			return 0;
		}

		if (!$options['all']) {
			return (is_array(reset($data))) ? static::depth(reset($data)) + 1 : 1;
		}
		$depth = [$options['count']];

		if (is_array($data) && reset($data) !== false) {
			foreach ($data as $value) {
				$depth[] = static::depth($value, [
					'all' => $options['all'],
					'count' => $options['count'] + 1
				]);
			}
		}
		return max($depth);
	}

	/**
	 * Collapses a multi-dimensional array into a single dimension, using a delimited array path
	 * for each array element's key, i.e. [['Foo' => ['Bar' => 'Far']]] becomes
	 * ['0.Foo.Bar' => 'Far'].
	 *
	 * @param  array $data array to flatten
	 * @param  array $options Available options are:
	 *               - `'separator'`: String to separate array keys in path (defaults to `'.'`).
	 *               - `'path'`: Starting point (defaults to null).
	 * @return array
	 */
	public static function flatten($data, $options = []) {
		$defaults = ['separator' => '.', 'path' => null];
		$options += $defaults;
		$result = [];

		if (!is_null($options['path'])) {
			$options['path'] .= $options['separator'];
		}
		foreach ($data as $key => $val) {
			if (!is_array($val)) {
				$result[$options['path'] . $key] = $val;
				continue;
			}
			$opts = ['separator' => $options['separator'], 'path' => $options['path'] . $key];
			$result += (array) static::flatten($val, $opts);
		}
		return $result;
	}

	/**
	 * Accepts a one-dimensional array where the keys are separated by a delimiter.
	 *
	 * @param  array $data The one-dimensional array to expand.
	 * @param  array $options The options used when expanding the array:
	 *               - `'separator'` _string_: The delimiter to use when separating keys.
	 *                 Defaults to `'.'`.
	 * @return array Returns a multi-dimensional array expanded from a one dimensional
	 *               dot-separated array.
	 */
	public static function expand($data, $options = []) {
		$defaults = ['separator' => '.'];
		$options += $defaults;
		$result = [];

		foreach ($data as $key => $val) {
			if (strpos($key, $options['separator']) === false) {
				if (!isset($result[$key])) {
					$result[$key] = $val;
				}
				continue;
			}
			list($path, $key) = explode($options['separator'], $key, 2);
			$path = is_numeric($path) ? intval($path) : $path;
			$result[$path][$key] = $val;
		}
		foreach ($result as $key => $value) {
			if (is_array($value)) {
				$result[$key] = static::expand($value, $options);
			}
		}
		return $result;
	}

	/**
	 * Merging recursively arrays.
	 *
	 * Override values for strings identical (unlike `array_merge_recursive()`).
	 *
	 * @param  array ... list of array to merge.
	 * @return array The merged array.
	 */
	public static function merge() {
		if (func_num_args() < 2) {
			throw new BadFunctionCallException("Not enough parameters.");
		}
		$args = func_get_args();
		$merged = array_shift($args);

		foreach ($args as $source) {
			foreach ($source as $key => $value) {
				if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
					$merged[$key] = static::merge($merged[$key], $value);
				} elseif (is_int($key)) {
					$merged[] = $value;
				} else {
					$merged[$key] = $value;
				}
			}
		}
		return $merged;
	}

	/**
	 * Normalizes a string or array list.
	 *
	 * @param  mixed   $list List to normalize.
	 * @param  boolean $assoc If `true`, `$list` will be converted to an associative array.
	 * @param  string  $sep If `$list` is a string, it will be split into an array with `$sep`.
	 * @param  boolean $trim If `true`, separated strings will be trimmed.
	 * @return array
	 */
	public static function normalize($list, $assoc = true, $sep = ',', $trim = true) {
		if (is_string($list)) {
			$list = explode($sep, $list);
			$list = ($trim) ? array_map('trim', $list) : $list;
			return ($assoc) ? static::normalize($list) : $list;
		}

		if (!is_array($list)) {
			return $list;
		}

		$keys = array_keys($list);
		$count = count($keys);
		$numeric = true;

		if (!$assoc) {
			for ($i = 0; $i < $count; $i++) {
				if (!is_int($keys[$i])) {
					$numeric = false;
					break;
				}
			}
		}

		if (!$numeric || $assoc) {
			$newList = array();
			for ($i = 0; $i < $count; $i++) {
				if (is_int($keys[$i]) && is_scalar($list[$keys[$i]])) {
					$newList[$list[$keys[$i]]] = null;
				} else {
					$newList[$keys[$i]] = $list[$keys[$i]];
				}
			}
			$list = $newList;
		}
		return $list;
	}

	/**
	 * Slices an array into two, separating them determined by an array of keys.
	 *
	 * Usage examples:
	 *
	 * @param  array        $subject Array that gets split apart
	 * @param  array|string $keys An array of keys or a single key as string
	 * @return array        An array containing both arrays, having the array with requested
	 *                      keys first and the remainder as second element
	 */
	public static function slice($data, $keys) {
		$removed = array_intersect_key($data, array_fill_keys((array) $keys, true));
		$data = array_diff_key($data, $removed);
		return [$data, $removed];
	}
}

?>