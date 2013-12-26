<?php
/**
 * Kahlan: PHP Testing Framework
 *
 * @copyright     Copyright 2013, CrysaLEAD
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace kahlan\analysis;

use Exception;
use SplFileObject;
use ReflectionClass;

class Inspector {

	/**
	 * The ReflectionClass instances cache.
	 *
	 * @var array
	 */
	protected static $_cache = [];

	/**
	 * Get the ReflectionClass instance of a class.
	 *
	 * @param  $class The class name to inspect.
	 * @return ReflectionClass
	 */
	public static function inspect($class) {
		if (!isset(static::$_cache[$class])) {
			static::$_cache[$class] = new ReflectionClass($class);
		}
		return static::$_cache[$class];
	}

	/**
	 * Get the parameters array of a class method.
	 *
	 * @param  $class  The class name.
	 * @param  $method The class method name.
	 * @param  $data   Some default values.
	 * @return array
	 */
	public static function parameters($class, $method, $data = null) {
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
	 * Return the type hint of a `ReflectionParameter` instance.
	 *
	 * @param  ReflectionMethod  $method A instance of `ReflectionParameter`.
	 * @return string            The parameter type hint.
	 */
	public static function typehint($parameter) {
		$typehint = '';
		if ($parameter->isArray()) {
			$typehint = 'array ';
		} elseif ($parameter->getClass()) {
			$typehint = $parameter->getClass()->getName() . ' ';
		} elseif (preg_match('/.*?\[ \<[^\>]+\> (\S+ )(.*?)\$/', (string) $parameter, $match)) {
			$typehint = $match[1];
		}
		return $typehint;
	}
}

?>