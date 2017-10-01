<?php
namespace Kahlan\Filter;

use Closure;
use Generator;
use Exception;
use InvalidArgumentException;

class Filters
{
    /**
     * Indicates if the filter system is enabled or not.
     *
     * @var boolean
     */
    protected static $_enabled = true;

    /**
     * An array of filters keyed by a callable id.
     *
     * @var array
     */
    protected static $_filters = [];

    /**
     * A cached array of filters indexed by callable id.
     *
     * @var array
     */
    protected static $_cachedFilters = [];

    /**
     * The interceptor instance if JIT patching is enabled.
     *
     * @var object|null
     */
    protected static $_interceptor = null;

    /**
     * Indicates whether or not the unpatching must clean up the created interceptor.
     *
     * @var boolean
     */
    protected static $_unpatch = false;

    /**
     * Applies a filter.
     *
     * @param  mixed  $context An instance or a fully-namespaced class name.
     * @param  string $method  A method name.
     * @param  string $filter  The filter to apply.
     * @return string          The filter id.
     *
     * @throws InvalidArgumentException
     */
    public static function apply($context, $method, $filter)
    {
        static::$_cachedFilters = [];

        if (is_object($context)) {
            $instance = $context;
            $class = get_class($context);
        } else {
            $class = $context;
            $instance = null;
        }

        $filter = $filter->bindTo($instance, $class);

        $ref = static::_ref($context, $method);
        if (!isset(static::$_filters[$ref])) {
            static::$_filters[$ref] = [$filter];
        } else {
            array_unshift(static::$_filters[$ref], $filter);
        }
        return $ref . '|' . (count(static::$_filters[$ref]) - 1);
    }

    /**
     * Generates a reference id from a provided callable definition.
     *
     * @param  mixed  $context An instance or fully-namespaced class name.
     * @param  string $method  A method name.
     * @return string          A reference id.
     */
    protected static function _ref($context, $method)
    {
        if (is_string($context)) {
            return $context . "::{$method}";
        }
        return get_class($context) . '#' . spl_object_hash($context) . "::{$method}";
    }

    /**
     * Detaches a filter.
     *
     * @param  object|string  $context An instance, a fully-namespaced class name or a filter id to detach.
     * @param  string         $method  The name of the method to remove filter on or none if a filter id
     *                                 has been provided in `$context`.
     * @return Closure|array           Returns the removed closure if `$context` is a filter id. Otherwise it returns the
     *                                 array of closures attached to the provided class/method reference.
     */
    public static function detach($context, $method = null)
    {
        if (func_num_args() === 2) {
            $value = static::_ref($context, $method);
        } else {
            $value = $context;
        }
        if (strpos($value, '|') !== false) {
            list($ref, $num) = explode('|', $value);
            if (!isset(static::$_filters[$ref][$num])) {
                throw new Exception("Unexisting `'{$value}'` filter id.");
            }
            $result = static::$_filters[$ref][$num];
            unset(static::$_filters[$ref][$num]);
        } else {
            if (!isset(static::$_filters[$value])) {
                throw new Exception("Unexisting `'{$value}'` filter reference id.");
            }
            $result = static::$_filters[$value];
            unset(static::$_filters[$value]);
        }
        return $result;
    }

    /**
     * Gets the filters.
     *
     * @return array The filters array indexed by reference id.
     */
    public static function get()
    {
        return static::$_filters;
    }

    /**
     * Sets the filters.
     *
     * @param array $filters The filters array indexed by reference id.
     */
    public static function set($filters)
    {
        static::$_filters = $filters;
    }

    /**
     * Gets the whole filters data or filters associated to a class/instance's method.
     *
     * @param  mixed       $context An instance or a fully-namespaced class name.
     * @param  string|null $method  The method name.
     * @return array                The attached filters.
     */
    public static function filters($context, $method)
    {
        $ref = static::_ref($context, $method);

        if (isset(static::$_cachedFilters[$ref])) {
            return static::$_cachedFilters[$ref];
        }

        $filters = isset(static::$_filters[$ref]) ?  static::$_filters[$ref] : [];

        if (is_object($context)) {
            $class = get_class($context);
        } elseif (!$class = get_parent_class($context)) {
            return $filters;
        }

        do {
            $parentRef = $class . "::{$method}";
            if (!isset(static::$_filters[$parentRef])) {
                continue;
            }
            $filters = array_merge($filters, static::$_filters[$parentRef]);
        } while ($class = get_parent_class($class));

        return static::$_cachedFilters[$ref] = $filters;
    }

    /**
     * Cuts the normal execution of a method to run all applied filter for the method.
     *
     * @param  mixed   $context  An instance or a fully-namespaced class name to run filters on.
     * @param  string  $method   The method name.
     * @param  array   $params   The parameters the pass to the original method.
     * @param  Closure $callback The original method closure.
     * @param  array   $filters  Additional filters to apply on this run.
     * @return mixed
     */
    public static function run($context, $method, $params, $callback, $filters = [])
    {
        if (static::$_enabled) {
            $filters = array_merge($filters, static::filters($context, $method));
        }
        $filters[] = $callback;
        $generator = static::_generator($filters);

        $next = function () use ($params, $generator, &$next) {
            $args = func_get_args() + $params;
            $closure = $generator->current();
            $generator->next();
            array_unshift($args, $next);
            return call_user_func_array($closure, $args);
        };
        return call_user_func_array($next, $params);
    }

    /**
     * Creates a generator from a filters array.
     *
     * @param  array     $filters The filters array.
     * @return Generator
     */
    protected static function _generator($filters)
    {
        foreach ($filters as $filter) {
            yield $filter;
        }
    }

    /**
     * Enables/disables the filter system.
     *
     * @param boolean $active
     */
    public static function enable($active = true)
    {
        static::$_enabled = $active;
    }

    /**
     * Removes filters for all classes.
     */
    public static function reset()
    {
        static::$_filters = [];
        static::$_enabled = true;
    }
}
