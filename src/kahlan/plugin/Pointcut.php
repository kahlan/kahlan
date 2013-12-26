<?php
/**
 * Kahlan: PHP Testing Framework
 *
 * @copyright     Copyright 2013, CrysaLEAD
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace kahlan\plugin;

class Pointcut {

	protected static $_classes = [
		'call' => 'kahlan\plugin\Call',
		'stub' => 'kahlan\plugin\Stub'
	];

	/**
	 * Point cut called before all method call.
	 *
	 * @return boolean If `true` is returned, the normal execution of the method is aborted.
	 */
	public static function before($name, $self, $params) {
		list($class, $name) = explode('::', $name);

		$call = static::$_classes['call'];

		if ($name === '__call' || $name === '__callStatic') {
			$name = array_shift($params);
			$params = array_shift($params);
		}
		$call::log($self, compact('name', 'params'));

		$stub = static::$_classes['stub'];
		if ($method = $stub::find($self, $name, $params)) {
			return $method;
		}
		return false;
	}

}

?>