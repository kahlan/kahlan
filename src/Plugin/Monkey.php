<?php
namespace Kahlan\Plugin;

use Kahlan\Suite;
use Kahlan\Plugin\Stub\Fct;
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
        $function = static::register($source);
        if (!$dest) {
            return $function;
        }
        if (class_exists($source)) {
            $function->reference($dest);
        } else {
            $function->andReturnUsing($dest);
        }
        return $function;
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

        $function = isset(static::$_registered[$name]) ? static::$_registered[$name] : null;

        if (!$isFunc) {
            $reference = $function ? $function->reference() : $name;
            if (is_object($reference)) {
                $substitute = $reference;
            }
            return $reference;
        }

        return function() use ($name, $function) {
            $args = func_get_args();
            if (Suite::registered($name)) {
                Calls::log(null, compact('name', 'args'));
            }

            if ($function && $function->matchArgs($args)) {
                return $function($args);
            }
            return call_user_func_array($name, $args);
        };
    }

    /**
     * Register a patch
     *
     * @param  mixed         $name A fully namespaced class/function name.
     * @return boolean|array
     */
    public static function register($name)
    {
        return static::$_registered[$name] = new Fct();
    }

    /**
     * Checks if a stub has been registered for a hash
     *
     * @param  mixed         $name A fully namespaced class/function name.
     * @return boolean|array
     */
    public static function registered($name = null)
    {
        if (!func_num_args()) {
            return array_keys(static::$_registered);
        }
        return isset(static::$_registered[$name]);
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
