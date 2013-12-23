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

	public static function inspect($class) {
		if (!isset(static::$_cache[$class])) {
			static::$_cache[$class] = new ReflectionClass($class);
		}
		return static::$_cache[$class];
	}

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
}

?>