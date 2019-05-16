<?php declare(strict_types=1);

namespace Kahlan\Analysis;

use ReflectionClass;

class Inspector
{
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
    public static function inspect(string $class): object
    {
        if (!isset(static::$_cache[$class])) {
            static::$_cache[$class] = new ReflectionClass($class);
        }
        return static::$_cache[$class];
    }

    /**
     * Gets the parameters array of a class method.
     *
     * @param  string $class  The class name.
     * @param  string $method The method name.
     * @param  array  $data   The default values.
     * @return array   The parameters array.
     */
    public static function parameters(string $class, string $method, ?array $data = null): array
    {
        $params = [];
        $reflection = Inspector::inspect($class);
        $parameters = $reflection->getMethod($method)->getParameters();
        if ($data === null) {
            return $parameters;
        }
        foreach ($data as $name => $value) {
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
     */
    public static function typehint(\ReflectionParameter $parameter): string
    {
        $typehint = '';
        if ($parameter->getClass()) {
            $typehint = '\\' . $parameter->getClass()->getName();
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
}
