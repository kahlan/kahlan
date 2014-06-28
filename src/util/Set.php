<?php
/**
 * Kahlan: PHP Testing Framework
 *
 * @copyright     Copyright 2013, CrysaLEAD
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace kahlan\util;

use Exception;
use BadFunctionCallException;

class Set
{
    /**
     * Merging recursively arrays.
     *
     * Override values for strings identical (unlike `array_merge_recursive()`).
     *
     * @param  array ... list of array to merge.
     * @return array The merged array.
     */
    public static function merge()
    {
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
     * Slices an array into two, separating them determined by an array of keys.
     *
     * Usage examples:
     *
     * @param  array        $subject Array that gets split apart
     * @param  array|string $keys An array of keys or a single key as string
     * @return array        An array containing both arrays, having the array with requested
     *                      keys first and the remainder as second element
     */
    public static function slice($data, $keys)
    {
        $removed = array_intersect_key($data, array_fill_keys((array) $keys, true));
        $data = array_diff_key($data, $removed);
        return [$data, $removed];
    }
}
