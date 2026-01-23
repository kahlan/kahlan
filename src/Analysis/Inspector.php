<?php
namespace Kahlan\Analysis;

use ReflectionClass;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionUnionType;

class Inspector
{
    /**
     * Normalizes a ReflectionNamedType name across PHP versions.
     *
     * In newer PHP versions (e.g. 8.5), `ReflectionNamedType::getName()` may
     * resolve `self`/`static`/`parent` to an FQCN. Casting the type to string
     * preserves the original keyword, so prefer that when possible.
     *
     * @param  object $type A instance of `ReflectionNamedType`.
     * @return string       The normalized type name.
     */
    protected static function _normalizedNamedTypeName($type)
    {
        // PHP 8.5 may resolve `self`/`static`/`parent` to an FQCN via `getName()`.
        // The string form historically preserves keywords, but can be deprecated
        // in some PHP versions/configs; suppress E_DEPRECATED so Kahlan won't
        // turn it into an exception.
        $raw = @ (string) $type;
        $raw = ltrim($raw, '?');
        $raw = ltrim($raw, '\\');

        if (in_array($raw, ['self', 'static', 'parent'], true)) {
            return $raw;
        }
        return ltrim($type->getName(), '\\');
    }

    /**
     * The ReflectionClass instances cache.
     *
     * @var array
     */
    protected static $_cache = [];

    /**
     * Gets the ReflectionClass instance of a class.
     *
     * @param  string $class The class name to inspect.
     * @return object        The ReflectionClass instance.
     */
    public static function inspect($class)
    {
        if (!isset(static::$_cache[$class])) {
            static::$_cache[$class] = new ReflectionClass($class);
        }
        return static::$_cache[$class];
    }

    /**
     * Gets the parameters array of a class method.
     *
     * @param  $class  The class name.
     * @param  $method The method name.
     * @param  $data   The default values.
     * @return array   The parameters array.
     */
    public static function parameters($class, $method, $data = null)
    {
        $params = [];
        $reflexion = Inspector::inspect($class);
        $parameters = $reflexion->getMethod($method)->getParameters();
        if ($data === null) {
            return $parameters;
        }
        foreach ($data as $key => $value) {
            $name = $key;
            if ($parameters) {
                $parameter = array_shift($parameters);
                $name = $parameter->getName();
            }
            $params[$name] = $value;
        }
        foreach ($parameters as $parameter) {
            if ($parameter->isDefaultValueAvailable()) {
                $params[$parameter->getName()] = $parameter->getDefaultValue();
            }
        }
        return $params;
    }

    /**
     * Returns the type hint of a `ReflectionParameter` instance.
     *
     * @param  object $parameter A instance of `ReflectionParameter`.
     * @return string            The parameter type hint.
     */
    public static function typehint($parameter)
    {
        $type = $parameter->getType();
        $typehint = '';
        if ($type) {
            if ($type instanceof ReflectionUnionType) {
                $result = [];
                foreach ($type->getTypes() as $t) {
                    $name = static::_normalizedNamedTypeName($t);
                    $isBuiltin = $t->isBuiltin() || in_array($name, ['self', 'static', 'parent'], true);
                    $result[] = ($isBuiltin ? '' : '\\') . $name;
                }
                return join('|', $result);
            }
            if ($type instanceof ReflectionIntersectionType) {
                $result = [];
                foreach ($type->getTypes() as $t) {
                    $name = static::_normalizedNamedTypeName($t);
                    $isBuiltin = ($t instanceof ReflectionNamedType && $t->isBuiltin()) || in_array($name, ['self', 'static', 'parent'], true);
                    $result[] = ($isBuiltin ? '' : '\\') . $name;
                }
                return join('&', $result);
            }
            $name = static::_normalizedNamedTypeName($type);
            $allowsNull = $name !== 'mixed' && $type->allowsNull() ? '?' : '';
            $isBuiltin = $type->isBuiltin() || in_array($name, ['self', 'static', 'parent'], true);
            return $allowsNull . ($isBuiltin ? '' : '\\') . $name;
        } elseif (preg_match('/.*?\[ \<[^\>]+\> (?:HH\\\)?(\w+)(.*?)\$/', (string) $parameter, $match)) {
            $typehint = $match[1];
            if ($typehint === 'integer') {
                $typehint = 'int';
            } elseif ($typehint === 'boolean') {
                $typehint = 'bool';
            } elseif ($typehint === 'mixed') {
                $typehint = '';
            }
        }
        return $typehint;
    }

    /**
     * Returns the type hint of a `ReflectionType` instance.
     *
     * @param  object $type A instance of `ReflectionType`.
     * @return string            The parameter type hint.
     */
    public static function returnTypehint($type)
    {
        if (!$type) {
            return '';
        }
        if ($type instanceof ReflectionUnionType) {
            $result = [];
            foreach ($type->getTypes() as $t) {
                $result[] = static::returnTypehint($t);
            }
            return join('|', $result);
        }
        if ($type instanceof ReflectionIntersectionType) {
            $result = [];
            foreach ($type->getTypes() as $t) {
                $result[] = static::returnTypehint($t);
            }
            return join('&', $result);
        }
        $name = static::_normalizedNamedTypeName($type);
        $allowsNull = $name !== 'mixed' && $type->allowsNull() ? '?' : '';
        $isBuiltin = $type->isBuiltin() || in_array($name, [ 'self', 'static', 'parent' ], true);
        return $allowsNull . ($isBuiltin ? '' : '\\') . $name;
    }
}
