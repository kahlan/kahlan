<?php
namespace Kahlan\Plugin;

use Kahlan\Suite;
use Kahlan\Plugin\Call\Calls;

class Monkey
{
    /**
     * Registered monkey patches.
     *
     * @var array
     */
    protected static $_registered = [];

    /**
     * Setup a monkey patch.
     *
     * @param string $source A fully namespaced reference string.
     * @param string $dest   A fully namespaced reference string.
     */
    public static function patch($source, $dest)
    {
        static::$_registered[$source] = $dest;
    }

    /**
     * Patches the string.
     *
     * @param  string  $namespace The namespace.
     * @param  string  $ref       The fully namespaced class/function reference string.
     * @param  boolean $isFunc    Boolean indicating if $ref is a function reference.
     * @return string             A fully namespaced reference.
     */
    public static function patched($namespace, $ref, $isFunc = true)
    {
        $name = $ref;
        if(!$isFunc || function_exists("{$namespace}\\{$ref}")) {
            $name = "{$namespace}\\{$ref}";
        }
        $closure = isset(static::$_registered[$name]) ? static::$_registered[$name] : $name;
        if (!$isFunc) {
            return $closure;
        }
        if (!Suite::registered($name)) {
            return $closure;
        }
        return function() use ($name, $closure) {
            $args = func_get_args();
            Calls::log(null, compact('name', 'args'));
            return call_user_func_array($closure, $args);
        };
    }

    /**
     * Clears the registered references.
     *
      * @param string $source A fully-namespaced reference string or `null` to clear all.
     */
    public static function reset($source = null)
    {
        if ($source === null) {
            static::$_registered = [];
            return;
        }
        unset(static::$_registered[$source]);
    }
}
