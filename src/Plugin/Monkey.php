<?php
namespace Kahlan\Plugin;

use Kahlan\Suite;
use Kahlan\Plugin\Stub\Method;
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
    public static function patch($source, $dest = null)
    {
        $source = ltrim($source, '\\');
        $method = static::$_registered[$source] = new Method();
        if (!$dest) {
            return $method;
        }
        $method->toBe($dest);
        return $method;
    }

    /**
     * Patches the string.
     *
     * @param  string  $namespace The namespace.
     * @param  string  $ref       The fully namespaced class/function reference string.
     * @param  boolean $isFunc    Boolean indicating if $ref is a function reference.
     * @return string             A fully namespaced reference.
     */
    public static function patched($namespace, $ref, $isFunc = true, &$substitute = null)
    {
        $name = $ref;

        if ($namespace) {
            if (!$isFunc || function_exists("{$namespace}\\{$ref}")) {
                $name = "{$namespace}\\{$ref}";
            }
        }

        $method = isset(static::$_registered[$name]) ? static::$_registered[$name] : null;
        $fake = $method ? $method->substitute() : null;

        if (!$isFunc) {
            if (is_object($fake)) {
                $substitute = $fake;
            }
            return $fake ?: $name;
        }

        return function() use ($name, $method, $fake) {
            $args = func_get_args();
            if (Suite::registered($name)) {
                Calls::log(null, compact('name', 'args'));
            }
            $function = $fake ?: $name;
            if (!$fake && $method && $method->matchArgs($args)) {
                return $method($args);
            }
            return call_user_func_array($function, $args);
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
