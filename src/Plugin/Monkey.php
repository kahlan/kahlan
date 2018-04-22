<?php
namespace Kahlan\Plugin;

use Exception;
use Kahlan\Suite;
use Kahlan\Plugin\Stub\Method;
use Kahlan\Plugin\Call\Calls;
use Kahlan\Jit\Patcher\Monkey as MonkeyPatcher;

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
        if (is_object($source) || class_exists($source)) {
            $reference = $source;
        } else {
            $reference = null;
            if (MonkeyPatcher::blacklisted($source)) {
                throw new Exception("Monkey patching `{$source}()` is not supported by Kahlan.");
            }
        }
        $method = static::$_registered[$source] = new Method(compact('reference'));
        if (!$dest) {
            return $method;
        }
        $method->toBe($dest);
        return $method;
    }

    /**
     * Patches the string.
     *
     * @param  string $namespace  The namespace.
     * @param  string $className  The fully namespaced class reference string.
     * @param  string $methodName The method/function name.
     * @return string             A fully namespaced reference.
     */
    public static function patched($namespace, $className, $methodName, &$substitute = null)
    {
        $name = $className ?: $methodName;

        if ($namespace && ($className || function_exists("{$namespace}\\{$name}"))) {
            $name = "{$namespace}\\{$name}";
        }

        $method = isset(static::$_registered[$name]) ? static::$_registered[$name] : null;

        if (!$methodName) {
            $fake = $method ? $method->substitute() : null;
            if (is_object($fake)) {
                $substitute = $fake;
            }
            return $fake ?: $name;
        }

        if (!$method) {
            if (!Suite::registered($name)) {
                return $name;
            }
        } elseif ($method->actsAsAReplacement()) {
            return $method->closure();
        }

        return function () use ($name, $method) {
            $args = func_get_args();

            if (Suite::registered($name)) {
                Calls::log(null, compact('name', 'args'));
            }
            if ($method && $method->matchArgs($args)) {
                return $method($args);
            }
            return call_user_func_array($name, $args);
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
