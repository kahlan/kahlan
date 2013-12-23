<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2013, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace kahlan\filter;

use Closure;

/**
 * The `Filters` class is the basis of Lithium's method filtering system: an efficient way to enable
 * event-driven communication between classes without tight coupling and without depending on a
 * centralized publish/subscribe system.
 */
class Filters extends \kahlan\util\Collection {

	/**
	 * The fully-namespaced class name of the class containing the method being filtered.
	 *
	 * @var string
	 */
	protected $_class = null;

	/**
	 * The name of the method being filtered by the current instance of `Filters`.
	 *
	 * @var string
	 */
	protected $_method = null;


	/**
	 * The params of the method being filtered.
	 *
	 * @var string
	 */
	protected $_params = [];

	/**
	 * Construct the collection object
	 */
	public function __construct($options = []) {
		parent::__construct($options);
		$defaults = ['class' => null, 'method' => null, 'params' => []];
		$options += $defaults;
		$this->_class = $options['class'];
		$this->_method = $options['method'];
		$this->_params = $options['params'];
	}

	/**
	 * Collects a set of filters to iterate. Creates a filter chain for the given class/method,
	 * executes it, and returns the value.
	 *
	 * @param  array $options The configuration options with which to create the filter chain.
	 *               Mainly, these options allow the `Filters` object to be queried for details
	 *               such as which class / method initiated it. Available keys:
	 *               - `'class'`: The name of the class that initiated the filter chain.
	 *               - `'method'`: The name of the method that initiated the filter chain.
	 *               - `'data'` _array_: An array of callable objects (usually closures) to be
	 *               iterated through. By default, execution will be nested such that the first
	 *               item will be executed first, and will be the last to return.
	 * @return mixed Returns the value returned by the first closure in `$options['data`]`.
	 */
	public static function run(array $options = array()) {
		$defaults = array('data' => [], 'class' => null, 'method' => null, 'params' => []);
		$options += $defaults;
		$chain = new Filters($options);
		$closure = $chain->rewind();
		return call_user_func_array($closure, array_merge([$chain], $options['params']));
	}

	/**
	 * Provides short-hand convenience syntax for filter chaining.
	 *
	 * @return mixed Returns the return value of the next filter in the chain.
	 */
	public function next() {
		$closure = parent::next();
		return call_user_func_array($closure, array_merge([$this], $this->_params));
	}

	/**
	 * Gets the params associated with this filter chain.
	 *
	 * @return array
	 */
	public function params() {
		return $this->_params;
	}

	/**
	 * Gets the method name associated with this filter chain. This is the method being filtered.
	 *
	 * @param boolean $full Whether to return the method name including the class name or not.
	 * @return string
	 */
	public function method($full = false) {
		return $full ? $this->_class . '::' . $this->_method : $this->_method;
	}
}

?>