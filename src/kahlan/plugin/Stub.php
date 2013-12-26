<?php
/**
 * Kahlan: PHP Testing Framework
 *
 * @copyright     Copyright 2013, CrysaLEAD
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace kahlan\plugin;

use Reflection;
use ReflectionMethod;
use InvalidArgumentException;
use kahlan\IncompleteException;
use kahlan\util\String;
use kahlan\analysis\Inspector;
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
			'class' => 'spec\plugin\stub\Stub' . static::$_index++,
			'extends' => ''
		];
		$options += $defaults;
		$interceptor = static::$_classes['interceptor'];

		if (!class_exists($options['class'], false)) {
			$code = static::generate($options);
			if ($patchers = $interceptor::instance()->patchers()) {
				$code = $patchers->process($code);
			}
			eval('?>' . $code);
		}
		return $options['class'];
	}

	/**
	 * Create a class definition.
	 *
	 * @param  array  $options Array of options. Options are:
	 *                - `'class'` : the fully-namespaced class name.
	 *                - `'extends'` : the fully-namespaced parent class name.
	 * @return string The generated class string content.
	 */
	public static function generate($options = []) {
		$defaults = [
			'class' => 'spec\plugin\stub\Stub' . static::$_index++,
			'extends' => '',
			'implements' => [],
			'uses' => []
		];
		$options += $defaults;

		$class = $options['class'];
		$namespace = '';
		if (($pos = strrpos($class, '\\')) !== false) {
			$namespace = substr($class, 0, $pos);
			$class = substr($class, $pos + 1);
		}

		if ($namespace) {
			$namespace = "namespace {$namespace};\n";
		}

		$uses = static::_generateUses($options['uses']);
		$extends = static::_generateExtends($options['extends']);
		$implements = static::_generateImplements($options['implements']);

		$methods = static::_generateClassMethods($options['extends']);
		$methods .= static::_generateInterfaceMethods($options['implements']);

return "<?php\n\n" . $namespace . <<<EOT

class {$class}{$extends}{$implements} {

	{$uses}
{$methods}

	public function __get(\$key){
		return new static();
	}

	public function __call(\$name, \$params) {
		return new static();
	}

	public static function __callStatic(\$name, \$params) {}
}
?>
EOT;

	}

	/**
	 * Create a `use` definition.
	 *
	 * @param  array  $uses An array of traits.
	 * @return string The generated `use` definition.
	 */
	protected static function _generateUses($uses) {
		if (!$uses) {
			return '';
		}
		$traits = [];
		foreach ((array) $uses as $use) {
			if (!trait_exists($use)) {
				throw new IncompleteException("Unexisting trait `{$use}`");
			}
			$traits[] = '\\' . ltrim($use, '\\');
		}
		return 'use ' . join(', ', $traits) . ';';
	}

	/**
	 * Create an `extends` definition.
	 *
	 * @param  string $extends The parent class name.
	 * @return string The generated `extends` definition.
	 */
	protected static function _generateExtends($extends) {
		if (!$extends) {
			return '';
		}
		return ' extends \\' . ltrim($extends, '\\');
	}

	/**
	 * Create an `implements` definition.
	 *
	 * @param  array  $uses An array of interfaces.
	 * @return string The generated `implements` definition.
	 */
	protected static function _generateImplements($implements) {
		if (!$implements) {
			return '';
		}
		$classes = [];
		foreach ((array) $implements as $implement) {
			$classes[] = '\\' . ltrim($implement, '\\');
		}
		return ' implements ' . join(', ', $classes);
	}

	/**
	 * Create methods definition from a class name.
	 *
	 * @param  string $class A class name.
	 * @param  int    $mask  The method mask to filter.
	 * @return string The generated methods.
	 */
	protected static function _generateClassMethods($class, $mask = ReflectionMethod::IS_ABSTRACT) {
		if (!$class) {
			return '';
		}
		$result = '';
		if (!class_exists($class)) {
			throw new IncompleteException("Unexisting interface `{$class}`");
		}
		$reflection = Inspector::inspect($class);
		$methods = $reflection->getMethods($mask);
		foreach ($methods as $method) {
			$result .= static::_generateMethod($method);
		}
		return $result;
	}

	/**
	 * Create methods definition from an interface array.
	 *
	 * @param  array  $interfaces A array on interfaces.
	 * @param  int    $mask  The method mask to filter.
	 * @return string The generated methods.
	 */
	protected static function _generateInterfaceMethods($interfaces, $mask = 255) {
		if (!$interfaces) {
			return '';
		}
		$result = '';
		foreach ($interfaces as $interface) {
			if (!interface_exists($interface)) {
				throw new IncompleteException("Unexisting interface `{$interface}`");
			}
			$reflection = Inspector::inspect($interface);
			$methods = $reflection->getMethods($mask);
			foreach ($methods as $method) {
				$result .= static::_generateMethod($method);
			}
		}
		return $result;
	}

	/**
	 * Create a method definition from a `ReflectionMethod` instance.
	 *
	 * @param  ReflectionMethod  $method A instance of `ReflectionMethod`.
	 * @return string            The generated method.
	 */
	protected static function _generateMethod($method) {
		$result = join(' ', Reflection::getModifierNames($method->getModifiers()));
		$result = preg_replace('/^abstract /', '', $result);
		$name = $method->getName();
		$body = '';
		if ($name === '__get' || $name === '__call' || $name === '__callStatic') {
			return '';
		}
		$parameters = static::_generateParameters($method);
		return "\tfunction {$name}({$parameters}) { {$body} }\n\n";
	}

	/**
	 * Create a parameters definition list from a `ReflectionMethod` instance.
	 *
	 * @param  ReflectionMethod  $method A instance of `ReflectionMethod`.
	 * @return string            The parameters definition list.
	 */
	protected static function _generateParameters($method){
		$params = [];
		foreach ($method->getParameters() as $num => $parameter) {
			$typehint = Inspector::typehint($parameter);
			$name = $parameter->getName();
			$name = ($name && $name !== '...') ? $name : 'param' . $num;
			$reference = $parameter->isPassedByReference() ? '&' : '';
			$default = '';
			if ($parameter->isOptional()) {
				if ($parameter->isDefaultValueAvailable()) {
					$default = var_export($parameter->getDefaultValue(), true);
				} else {
					$default = 'null';
				}
			}

			$params[] = "{$typehint}{$reference}\${$name}{$default}";
		}
		return join(', ', $params);
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