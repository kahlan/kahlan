<?php
namespace Kahlan\Plugin;

use InvalidArgumentException;
use Reflection;
use ReflectionMethod;
use ReflectionClass;
use Kahlan\Suite;
use Kahlan\IncompleteException;
use Kahlan\Analysis\Inspector;
use Kahlan\Plugin\Stub\Method;

class Stub
{
    /**
     * Class dependencies.
     *
     * @var array
     */
    protected static $_classes = [
        'parser'   => 'Kahlan\Jit\Parser',
        'pointcut' => 'Kahlan\Jit\Patcher\Pointcut'
    ];

    /**
     * The pointcut patcher instance.
     *
     * @var object
     */
    protected static $_pointcut = null;

    /**
     * Registered stubbed instance/class methods.
     *
     * @var array
     */
    protected static $_registered = [];

    /**
     * Stub index counter.
     *
     * @var integer
     */
    protected static $_index = 0;

    /**
     * Stubbed methods.
     *
     * @var Method[]
     */
    protected $_methods = [];

    /**
     * Generic stubs.
     *
     * @var Method[]
     */
    protected $_stubs = [];

    /**
     * Stub index counter.
     *
     * @var integer
     */
    protected $_needToBePatched = false;

    /**
     * The Constructor.
     *
     * @param mixed $reference An instance or a fully-namespaced class name.
     */
    public function __construct($reference)
    {
        $reference = $this->_reference($reference);
        $isString = is_string($reference);
        if ($isString) {
            if (!class_exists($reference)) {
                throw new InvalidArgumentException("Can't Stub the unexisting class `{$reference}`.");
            }
            $reference = ltrim($reference, '\\');
            $reflection = Inspector::inspect($reference);
        } else {
            $reflection = Inspector::inspect(get_class($reference));
        }

        if (!$reflection->isInternal()) {
            $this->_reference = $reference;
            return;
        }
        if (!$isString) {
            throw new InvalidArgumentException("Can't Stub built-in PHP instances, create a test double using `Stub::create()`.");
        }
        $this->_needToBePatched = true;
        return $this->_reference = $reference;
    }

    /**
     * Return the actual reference which must be used.
     *
     * @param mixed $reference An instance or a fully-namespaced class name.
     * @param mixed            The reference or the monkey patched one if exist.
     */
    protected function _reference($reference)
    {
        if (!is_string($reference)) {
            return $reference;
        }

        $pos = strrpos($reference, '\\');
        if ($pos !== false) {
            $namespace = substr($reference, 0, $pos);
            $basename = substr($reference, $pos + 1);
        } else {
            $namespace = null;
            $basename = $reference;
        }
        $substitute = null;
        $reference = Monkey::patched($namespace, $basename, false, $substitute);

        return $substitute ?: $reference;
    }

    /**
     * Getd/Setd stubs for methods or get stubbed methods array.
     *
     * @param  array    $name An array of method names.
     * @return Method[]       Return the array of stubbed methods.
     */
    public function methods($name = [])
    {
        if (!func_num_args()) {
            return $this->_methods;
        }
        foreach ($name as $method => $returns) {
            if (is_callable($returns)) {
                $this->method($method, $returns);
            } elseif (is_array($returns)) {
                $stub = $this->method($method);
                call_user_func_array([$stub, 'andReturn'], $returns);
            } else {
                $error = "Stubbed method definition for `{$method}` must be a closure or an array of returned value(s).";
                throw new InvalidArgumentException($error);
            }
        }
    }

    /**
     * Stubs a method.
     *
     * @param  string   $path    Method name or array of stubs where key are method names and
     *                           values the stubs.
     * @param  string   $closure The stub implementation.
     * @return Method[]          The created array of method instances.
     * @return Method            The stubbed method instance.
     */
    public function method($path, $closure = null)
    {
        if ($this->_needToBePatched) {
            $layer = Stub::classname();
            Monkey::patch($this->_reference, $layer);
            $this->_needToBePatched = false;
            $this->_reference = $layer;
        }

        $reference = $this->_reference;

        $parts = preg_split('~((?:->|::)[^-:]+)~', $path, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

        $names = [];
        foreach ($parts as $part) {
            $names[] = isset($part[0]) && $part[0] === '-' ? substr($part, 2) : $part;
        }

        $methods = [];
        $total = count($names);

        foreach ($names as $index => $name) {
            if (preg_match('/^::.*/', $name)) {
                $reference = is_object($reference) ? get_class($reference) : $reference;
            }

            $hash = Suite::hash($reference);
            if (!isset(static::$_registered[$hash])) {
                static::$_registered[$hash] = new static($reference);
            }

            $instance = static::$_registered[$hash];
            if (is_object($reference)) {
                Suite::register(get_class($reference));
            } else {
                Suite::register($reference);
            }
            if (!isset($instance->_methods[$name])) {
                $instance->_methods[$name] = [];
                $instance->_stubs[$name] = Stub::create();
            }

            $method = new Method([
                'reference' => $reference,
                'name'      => $name
            ]);
            $methods[] = $method;
            array_unshift($instance->_methods[$name], $method);

            if ($index < $total - 1) {
                $reference = $instance->_stubs[$name];
                $method->andReturn($instance->_stubs[$name]);
            }
        }

        $method = end($methods);
        if ($closure) {
            $method->andReturnUsing($closure);
        }
        return $method;
    }

    /**
     * Stubs class methods.
     *
     * @param  object|string $reference An instance or a fully-namespaced class name.
     * @return self                     The Stub instance.
     */
    public static function on($reference)
    {
        $hash = Suite::hash($reference);
        if (isset(static::$_registered[$hash])) {
            return static::$_registered[$hash];
        }
        return static::$_registered[$hash] = new static($reference);
    }

    /**
     * Finds a stub.
     *
     * @param  mixed       $references An instance or a fully namespaced class name.
     *                                 or an array of that.
     * @param  string      $method     The method name.
     * @param  array       $args       The required arguments.
     * @return object|null             Return the subbed method or `null` if not founded.
     */
    public static function find($references, $method = null, $args = null)
    {
        $references = (array) $references;
        $stub = null;
        $refs = [];
        foreach ($references as $reference) {
            $hash = Suite::hash($reference);
            if (!isset(static::$_registered[$hash])) {
                continue;
            }
            $stubs = static::$_registered[$hash]->methods();

            if (!isset($stubs[$method])) {
                continue;
            }

            foreach ($stubs[$method] as $stub) {
                $call['name'] = $method;
                $call['args'] = $args;
                if ($stub->match($call)) {
                    return $stub;
                }
            }
        }
        return false;
    }

    /**
     * Creates a polyvalent instance.
     *
     * @param  array  $options Array of options. Options are:
     *                         - `'class'`   _string_: the fully-namespaced class name.
     *                         - `'extends'` _string_: the fully-namespaced parent class name.
     *                         - `'args'`    _array_:  arguments to pass to the constructor.
     *                         - `'methods'` _string_: override the method defined.
     * @return object          The created instance.
     */
    public static function create($options = [])
    {
        $class = static::classname($options);

        if (isset($options['args'])) {
            $refl = new ReflectionClass($class);
            $instance = $refl->newInstanceArgs($options['args']);
        } else {
            $instance = new $class();
        }
        return $instance;
    }

    /**
     * Creates a polyvalent static class.
     *
     * @param  array  $options Array of options. Options are:
     *                         - `'class'` : the fully-namespaced class name.
     *                         - `'extends'` : the fully-namespaced parent class name.
     * @return string          The created fully-namespaced class name.
     */
    public static function classname($options = [])
    {
        $defaults = ['class' => 'Kahlan\Spec\Plugin\Stub\Stub' . static::$_index++];
        $options += $defaults;

        if (!static::$_pointcut) {
            $pointcut = static::$_classes['pointcut'];
            static::$_pointcut = new $pointcut();
        }

        if (!class_exists($options['class'], false)) {
            $parser = static::$_classes['parser'];
            $code = static::generate($options);
            $nodes = $parser::parse($code);
            $code = $parser::unparse(static::$_pointcut->process($nodes));
            eval('?>' . $code);
        }
        return $options['class'];
    }

    /**
     * Creates a class definition.
     *
     * @param  array  $options Array of options. Options are:
     *                         - `'class'`      _string_ : the fully-namespaced class name.
     *                         - `'extends'`    _string_ : the fully-namespaced parent class name.
     *                         - `'implements'` _array_  : the implemented interfaces.
     *                         - `'uses'`       _array_  : the used traits.
     *                         - `'methods'`    _array_  : the methods to stubs.
     *                         - `'layer'`      _boolean_: indicate if public methods should be layered.
     * @return string          The generated class string content.
     */
    public static function generate($options = [])
    {
        $defaults = [
            'class'      => 'spec\plugin\stub\Stub' . static::$_index++,
            'extends'    => '',
            'implements' => [],
            'uses'       => [],
            'methods'    => [],
            'layer'      => null,
            'openTag'    => true,
            'closeTag'   => true
        ];
        $options += $defaults;

        if ($options['extends']) {
            $options += ['magicMethods' => false];
        } else {
            $options += ['magicMethods' => true];
        }

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

        $methods = static::_generateMethodStubs($options['methods'], $options['magicMethods']);
        if ($options['extends']) {
            $methods += static::_generateClassMethods($options['extends'], $options['layer']);
        }
        $methods += static::_generateInterfaceMethods($options['implements']);

        $methods = $methods ? '    ' . join("\n    ", $methods) : '';

        $openTag = $options['openTag'] ? "<?php\n" : '';
        $closeTag = $options['closeTag'] ? "?>" : '';

return $openTag . $namespace . <<<EOT

class {$class}{$extends}{$implements} {

{$uses}{$methods}

}
$closeTag
EOT;

    }

    /**
     * Returns Magic Methods definitions.
     *
     * @return array
     */
    public static function _getMagicMethods()
    {
        return [
            '__construct'    =>  "public function __construct() {}",
            '__destruct'     =>  "public function __destruct() {}",
            '__call'         =>  "public function __call(\$name, \$args) { return new static(); }",
            '::__callStatic' =>  "public static function __callStatic(\$name, \$args) { return get_called_class(); }",
            '__get'          =>  "public function __get(\$key){ return new static(); }",
            '__set'          =>  "public function __set(\$key, \$value) { \$this->{\$key} = \$value; }",
            '__isset'        =>  "public function __isset(\$key) { return isset(\$this->{\$key}); }",
            '__unset'        =>  "public function __unset(\$key) { unset(\$this->{\$key}); }",
            '__sleep'        =>  "public function __sleep() { return []; }",
            '__wakeup'       =>  "public function __wakeup() {}",
            '__toString'     =>  "public function __toString() { return get_class(); }",
            '__invoke'       =>  "public function __invoke() {}",
            '__set_sate'     =>  "public function __set_sate(\$properties) {}",
            '__clone'        =>  "public function __clone() {}"
        ];
    }

    /**
     * Creates a `use` definition.
     *
     * @param  array  $uses An array of traits.
     * @return string       The generated `use` definition.
     */
    protected static function _generateUses($uses)
    {
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
        return '    use ' . join(', ', $traits) . ';';
    }

    /**
     * Creates an `extends` definition.
     *
     * @param  string $extends The parent class name.
     * @return string          The generated `extends` definition.
     */
    protected static function _generateExtends($extends)
    {
        if (!$extends) {
            return '';
        }
        return ' extends \\' . ltrim($extends, '\\');
    }

    /**
     * Creates an `implements` definition.
     *
     * @param  array  $uses An array of interfaces.
     * @return string       The generated `implements` definition.
     */
    protected static function _generateImplements($implements)
    {
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
     * Creates method stubs.
     *
     * @param  array   $methods  An array of method definitions.
     * @param  boolean $defaults If `true`, Magic Methods will be appended.
     * @return string            The generated method definitions.
     */
    protected static function _generateMethodStubs($methods, $defaults = true)
    {
        $result = [];
        $methods = $methods !== null ? (array) $methods : [];

        if ($defaults) {
            $methods = array_merge($methods, array_keys(static::_getMagicMethods()));
        }
        $methods = array_unique($methods);

        $magicMethods = static::_getMagicMethods();

        foreach ($methods as $name) {
            if (isset($magicMethods[$name])) {
                $result[$name] = $magicMethods[$name];
            } else {
                $static = $return = '';
                if ($name[0] === '&') {
                    $return = '$r = null; return $r;';
                }
                if (preg_match('/^&?::.*/', $name)) {
                    $static = 'static ';
                    $name = substr($name, 2);
                }
                $result[$name] = "public {$static}function {$name}() {{$return}}";
            }
        }

        return $result;
    }

    /**
     * Creates method definitions from a class name.
     *
     * @param  string  $class A class name.
     * @param  boolean $layer If `true`, all public methods are "overriden".
     * @return string         The generated methods.
     */
    protected static function _generateClassMethods($class, $layer = null)
    {
        $result = [];
        if (!class_exists($class)) {
            throw new IncompleteException("Unexisting parent class `{$class}`");
        }
        $result = static::_generateAbstractMethods($class);

        if ($layer === false) {
            return $result;
        }

        $reflection = Inspector::inspect($class);

        if (!$layer && !$reflection->isInternal()) {
            return $result;
        }

        $finals = $reflection->getMethods(ReflectionMethod::IS_FINAL);
        $methods = array_diff($reflection->getMethods(ReflectionMethod::IS_PUBLIC), $finals);
        foreach ($methods as $method) {
            $result[$method->getName()] = static::_generateMethod($method, true);
        }
        return $result;
    }

    /**
     * Creates method definitions from a class name.
     *
     * @param  string  $class A class name.
     * @param  integer $mask  The method mask to filter.
     * @return string         The generated methods.
     */
    protected static function _generateAbstractMethods($class)
    {
        $result = [];
        if (!class_exists($class)) {
            throw new IncompleteException("Unexisting parent class `{$class}`");
        }
        $reflection = Inspector::inspect($class);
        $methods = $reflection->getMethods(ReflectionMethod::IS_ABSTRACT);
        foreach ($methods as $method) {
            $result[$method->getName()] = static::_generateMethod($method);
        }
        return $result;
    }

    /**
     * Creates method definitions from an interface array.
     *
     * @param  array   $interfaces A array on interfaces.
     * @param  integer $mask       The method mask to filter.
     * @return string              The generated methods.
     */
    protected static function _generateInterfaceMethods($interfaces, $mask = 255)
    {
        if (!$interfaces) {
            return [];
        }
        $result = [];
        foreach ((array) $interfaces as $interface) {
            if (!interface_exists($interface)) {
                throw new IncompleteException("Unexisting interface `{$interface}`");
            }
            $reflection = Inspector::inspect($interface);
            $methods = $reflection->getMethods($mask);
            foreach ($methods as $method) {
                $result[$method->getName()] = static::_generateMethod($method);
            }
        }
        return $result;
    }

    /**
     * Creates a method definition from a `ReflectionMethod` instance.
     *
     * @param  object $method A instance of `ReflectionMethod`.
     * @return string         The generated method.
     */
    protected static function _generateMethod($method, $callParent = false)
    {
        $result = join(' ', Reflection::getModifierNames($method->getModifiers()));
        $result = preg_replace('/abstract\s*/', '', $result);
        $name = $method->getName();
        $parameters = static::_generateSignature($method);
        $type = static::_generateReturnType($method);
        $body = "{$result} function {$name}({$parameters}) {$type}{";
        if ($callParent) {
            $parameters = static::_generateParameters($method);
            $return = 'return ';
            if ($method->isConstructor() || $method->isDestructor()) {
                $return = '';
            }
            $body .= "{$return}parent::{$name}({$parameters});";
        }
        return $body . "}";
    }

    /**
     * Extract the return type of a method.
     *
     * @param  objedct $method A instance of `ReflectionMethod`.
     * @return string          The return type.
     */
    protected static function _generateReturnType($method)
    {
        if (PHP_MAJOR_VERSION < 7) {
            return '';
        }
        $type = $method->getReturnType();
        if ($type) {
            if (!$type->isBuiltin()) {
                $type = '\\' . $type;
            }
            if (defined('HHVM_VERSION')) {
                $type = preg_replace('~\\\?HH\\\(mixed|void)?~', '', $type);
            }
        }
        return $type ? ": {$type} " : '';
    }

    /**
     * Creates a parameters signature of a `ReflectionMethod` instance.
     *
     * @param  object $method A instance of `ReflectionMethod`.
     * @return string         The parameters definition list.
     */
    protected static function _generateSignature($method)
    {
        $params = [];
        $isVariadic = PHP_MAJOR_VERSION >= 7 ? $method->isVariadic() : false;

        foreach ($method->getParameters() as $num => $parameter) {
            $typehint = Inspector::typehint($parameter);
            $name = $parameter->getName();
            $name = ($name && $name !== '...') ? $name : 'param' . $num;
            $reference = $parameter->isPassedByReference() ? '&' : '';
            $default = '';
            if ($parameter->isDefaultValueAvailable()) {
                $default = var_export($parameter->getDefaultValue(), true);
                $default = ' = ' . preg_replace('/\s+/', '', $default);
            } elseif ($parameter->isOptional()) {
                if ($isVariadic && $parameter->isVariadic()) {
                    $reference = '...';
                    $default = '';
                } else {
                    $default = ' = NULL';
                }
            }
            $typehint = $typehint ? $typehint . ' ' : $typehint;
            $params[] = "{$typehint}{$reference}\${$name}{$default}";
        }
        return join(', ', $params);
    }

    /**
     * Creates a parameters list from a `ReflectionMethod` instance.
     *
     * @param  object $method A instance of `ReflectionMethod`.
     * @return string         The parameters definition list.
     */
    protected static function _generateParameters($method)
    {
        $params = [];
        foreach ($method->getParameters() as $num => $parameter) {
            $name = $parameter->getName();
            $name = ($name && $name !== '...') ? $name : 'param' . $num;
            $params[] = "\${$name}";
        }
        return join(', ', $params);
    }

    /**
     * Checks if a stub has been registered for a hash
     *
     * @param  mixed         $hash An instance hash or a fully namespaced class name.
     * @return boolean|array
     */
    public static function registered($hash = null)
    {
        if (!func_num_args()) {
            return array_keys(static::$_registered);
        }
        return isset(static::$_registered[$hash]);
    }

    /**
     * Clears the registered references.
     *
     * @param string $reference An instance or a fully namespaced class name or `null` to clear all.
     */
    public static function reset($reference = null)
    {
        if ($reference === null) {
            static::$_registered = [];
            Suite::reset();
            return;
        }
        unset(static::$_registered[Suite::hash($reference)]);
    }
}
