<?php
/**
 * Kahlan: PHP Testing Framework
 *
 * @copyright     Copyright 2013, CrysaLEAD & Craftsmen
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace kahlan\plugin;

use InvalidArgumentException;
use kahlan\util\String;
use kahlan\plugin\stub\Method;

class Stub {

	/**
	 * Class dependencies.
	 *
	 * @var array
	 */
	protected static $_classes = [
		'interceptor' => 'kahlan\jit\Interceptor'
	];

	/**
	 * Registered stubbed instance/class methods
	 *
	 * @var array
	 */
	protected static $_registered = [];

	/**
	 * Stubbed methods
	 *
	 * @var array
	 */
	protected $_stubs = [];

	/**
	 * Stub index counter
	 *
	 * @var array
	 */
	protected static $_index = 0;

	/**
	 * Constructor
	 *
	 * @param mixed $reference An instance or a fully namespaced class name.
	 */
	public function __construct($reference) {
		$this->_reference = $reference;
	}

	/**
	 * Set stubs for methods or get stubbed methods array.
	 *

	 * @return mixed Return the array of stubbed methods.
	 */
	public function methods() {
		return $this->_stubs;
	}

	/**
	 * Stub class method.
	 *
	 * @param mixed $name Method name or array of stubs where key are method names and
	 *              values the stubs.
	 */
	public function method($name) {
		if (is_array($name)) {
			foreach ($name as $method => $returns) {
				$stub = $this->method($method);
				call_user_func_array([$stub, 'andReturn'], (array) $returns);
			}
			return;
		}

		$static = false;
		$reference = $this->_reference;
		if (preg_match('/^::.*/', $name)) {
			$static = true;
			$reference = is_object($reference) ? get_class($reference) : $reference;
			$name = substr($name, 2);
		}
		if (!isset(static::$_registered[String::hash($reference)])) {
			static::$_registered[String::hash($reference)] = $this;
		}
		return $this->_stubs[$name] = new Method(compact('name', 'static'));
	}

	/**
	 * Stub class methods.
	 *
	 * @param mixed $reference An instance or a fully namespaced class name.
	 */
	public static function on($reference) {
		$hash = String::hash($reference);
		if (isset(static::$_registered[$hash])) {
			return static::$_registered[$hash];
		}
		return new static($reference);
	}

	/**
	 * Find a stub.
	 *
	 * @param  mixed       $reference An instance or a fully namespaced class name.
	 * @param  string      $method    The method name.
	 * @param  array       $params    The required arguments.
	 * @return object|null Return the subbed method or `null` if not founded.
	 */
	public static function find($reference, $method = null, $params = []) {
		$stub = null;
		$refs = [String::hash($reference)];
		if (is_object($reference)) {
			$refs[] = get_class($reference);
		}
		foreach ($refs as $ref) {
			if (isset(static::$_registered[$ref])) {
				$stubs = static::$_registered[$ref]->methods();
				if (isset($stubs[$method])) {
					$stub = $stubs[$method];
					$call['name'] = $method;
					$call['static'] = !is_object($reference);
					$call['params'] = $params;
					return $stub->match($call) ? $stub : false;
				}
			}
		}
		return false;
	}

	/**
	 * Create a polyvalent instance.
	 *
	 * @param  array  $options Array of options. Options are:
	 *                - `'class'` : the fully-namespaced class name.
	 *                - `'extends'` : the fully-namespaced parent class name.
	 * @return string The created instance.
	 */
	public static function create($options = []) {
		$class = static::classname($options);
		return new $class();
	}

	/**
	 * Create a polyvalent static class.
	 *
	 * @param  array  $options Array of options. Options are:
	 *                - `'class'` : the fully-namespaced class name.
	 *                - `'extends'` : the fully-namespaced parent class name.
	 * @return string The created fully-namespaced class name.
	 */
	public static function classname($options = []) {
		$defaults = [
			'class' => 'kahlan\plugin\stub\Stub' . static::$_index++,
			'extends' => ''
		];
		$options += $defaults;
		$interceptor = static::$_classes['interceptor'];

		if (!class_exists($options['class'], false)) {
			$code = static::generate($options);
			if ($patcher = $interceptor::instance()->patcher()) {
				$code = $patcher->process($code);
			}
			eval('?>' . $code);
		}
		return $options['class'];
	}

	/**
	 * Create a Object.
	 *
	 * @param  array  $options Array of options. Options are:
	 *                - `'class'` : the fully-namespaced class name.
	 *                - `'extends'` : the fully-namespaced parent class name.
	 * @return string The generated class string content.
	 */
	public static function generate($options = []) {
		extract($options);

		if (($pos = strrpos($class, '\\')) !== false) {
			$namespace = substr($class, 0, $pos);
			$class = substr($class, $pos + 1);
		} else {
			$namespace = '';
		}

		if ($extends) {
			$extends = ' extends \\' . ltrim($extends, '\\');
		}

		if ($namespace) {
			$namespace = "<?php\n\nnamespace {$namespace};\n";
		}

return $namespace . <<<EOT

class {$class}{$extends} implements \ArrayAccess, \Iterator {

	public function __get(\$key){
		return new static();
	}

	public function __set(\$key, \$value) {}

	public function __call(\$name, \$params) {
		return new static();
	}

	public static function __callStatic(\$name, \$params) {}

	public function offsetExists(\$offset) {}

	public function offsetGet(\$offset) {}

	public function offsetSet(\$offset, \$value) {}

	public function offsetUnset(\$offset) {}

	public function key() {}

	public function current() {}

	public function next() {}

	public function rewind() {}

	public function valid() {
		return false;
	}
}
?>
EOT;

	}

	/**
	 * Clear the registered references.
	 *
	 * @param string $reference An instance or a fully namespaced class name or `null` to clear all.
	 */
	public static function clear($reference = null) {
		if ($reference === null) {
			static::$_registered = [];
			return;
		}
		unset(static::$_registered[String::hash($reference)]);
	}
}

?>