<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2013, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace kahlan\filter;

trait Filtering {

	protected $_methodFilters = array();

	/**
	 * Apply a closure to a method of the current static object.
	 *
	 * @param string|array $method  The name of the method to apply the closure to. Can either be
	 *                     a single method name as a string, or an array of method names.
	 * @param closure      $closure The clousure that is used to filter the method.
	 */
	public function applyFilter($methods, $closure) {
		$class = get_called_class();
		$closure = $closure->bindTo($this, $class);
		foreach ((array) $methods as $method) {
			$this->_methodFilters[$class][$method][] = $closure;
		}
	}

	/**
	 * Executes a set of filters against a method by taking a method's main implementation as a
	 * callback, and iteratively wrapping the filters around it.
	 *
	 * @param string|array $method   The name of the method being executed, or an array containing
	 *                     the name of the class that defined the method, and the method name.
	 * @param array        $params   An array containing all the parameters passed into
	 *                     the method.
	 * @param Closure      $callback The method's implementation, wrapped in a closure.
	 * @return mixed
	 */
	protected function _filter($method, $params, $callback) {
		$class = get_called_class();
		if (empty($this->_methodFilters[$class][$method])) {
			$this->_methodFilters[$class][$method] = [];
		}
		$data = array_merge($this->_methodFilters[$class][$method], [$callback]);
		return Filters::run(compact('data', 'class', 'method', 'params'));
	}

}

?>