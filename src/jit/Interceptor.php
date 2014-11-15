<?php
namespace kahlan\jit;

use RuntimeException;
use Composer\Autoload\ClassLoader;

class Interceptor {

    /**
     * Cache path.
     *
     * @var string
     */
    protected $_cache = '';

    /**
     * Method name to the delegated the parent for finding files.
     *
     * @var string
     */
    protected $_findFile = 'findFile';

    /**
     * Method name to the delegated the parent for getting the class map.
     *
     * @var string
     */
    protected $_getClassMap = 'getClassMap';

    /**
     * Method name to the delegated the parent for getting PSR-0 prefixes.
     *
     * @var string
     */
    protected $_getPrefixes = 'getPrefixes';

    /**
     * Method name to the delegated the parent for getting PSR-4 prefixes.
     *
     * @var string
     */
    protected $_getPrefixesPsr4 = 'getPrefixesPsr4';

    /**
     * Is cached file are persistent.
     *
     * @var array
     */
    protected $_persistent = false;

    /**
     * The patchers container.
     *
     * @var object
     */
    protected $_patchers = null;

    /**
     * Allowed namespaces/classes for being patched (if empty, mean all is allowed).
     *
     * @var array
     */
    protected $_include = [];

    /**
     * Namespaces/classes which must not be patched.
     *
     * @var array
     */
    protected $_exclude = [];

    /**
     * Original loader reference.
     *
     * @var array
     */
    protected static $_original = null;

    /**
     * Overrided loader reference.
     *
     * @var array
     */
    protected static $_loader = null;

    /**
     * Constructs
     *
     * @param array $options Options for the constructor.
     */
    public function __construct($options = [])
    {
        $defaults = [
            'patchers'        => null,
            'exclude'         => [],
            'include'         => ['*'],
            'persistent'      => true,
            'findFile'        => 'findFile',
            'getClassMap'     => 'getClassMap',
            'getPrefixes'     => 'getPrefixes',
            'getPrefixesPsr4' => 'getPrefixesPsr4',
            'cache'           => rtrim(sys_get_temp_dir(), DS) . DS . 'kahlan'
        ];
        $options += $defaults;
        $this->_patchers = $options['patchers'];
        $this->_findFile = $options['findFile'];
        $this->_getClassMap = $options['getClassMap'];
        $this->_getPrefixes = $options['getPrefixes'];
        $this->_getPrefixesPsr4 = $options['getPrefixesPsr4'];
        $this->_cache = rtrim($options['cache'], DS);
        $this->_persistent = $options['persistent'];
        $this->_exclude = $options['exclude'] ? (array) $options['exclude'] : ['kahlan\\'];
        $this->_include = (array) $options['include'];
    }

    /**
     * Patch the autoloader to be intercepted by the current autoloader.
     *
     * @param  array $options Options for the interceptor autoloader.
     * @throws RuntimeException
     */
    public static function patch($options = [])
    {
        if (static::$_loader) {
            throw new RuntimeException("An interceptor is already attached.");
        }
        $defaults = [
            'loader'   => null,
            'method'   => 'loadClass',
            'patchers' => null
        ];
        $options += $defaults;
        $loader = $options['loader'] ?: static::composer();
        if (!$loader) {
            throw new RuntimeException("The loader option need to be a valid autoloader.");
        }
        if (!spl_autoload_unregister($loader)) {
            throw new RuntimeException("The loader option need to be a valid registered autoloader.");
        }
        static::$_original = $loader;
        static::loader([new static($options), $options['method']]);
    }

    /**
     * Look for the composer autoloader.
     *
     * @param  array $options Options for the interceptor autoloader.
     * @return mixed The founded composer autolaoder or `null` if not found.
     */
    public static function composer()
    {
        $loaders = spl_autoload_functions();
        foreach ($loaders as $key => $loader) {
            if (is_array($loader) && ($loader[0] instanceof ClassLoader)) {
                return $loader;
            }
        }
    }

    /**
     * Returns the interceptor autoloader instance.
     *
     * @return object|null
     */
    public static function instance()
    {
        if (isset(static::$_loader[0]) && static::$_loader[0] instanceof static) {
            return static::$_loader[0];
        }
    }

    /**
     * Manualy load files.
     *
     * @param array An array of files to load.
     */
    public static function loadFiles($files)
    {
        $files = (array) $files;
        if (!$instance = static::instance()) {
            return false;
        }

        $success = true;
        foreach ($files as $file) {
            $instance->loadFile($file);
        }
        return true;
    }

    /**
     * Returns the original autoloader reference.
     *
     * @return array
     */
    public static function original()
    {
        return static::$_original;
    }

    /**
     * Returns the original autoloader.
     *
     * @return array
     */
    public static function originalInstance()
    {
        return is_array(static::$_original) ? static::$_original[0] : static::$_original;
    }

    /**
     * Gets/Sets the current loader.
     *
     * @param  array $loader A autoloader reference.
     * @return mixed Returns `true` on success, `false` otherwise or the loader value if get.
     */
    public static function loader($loader = null)
    {
        if ($loader === null) {
            return static::$_loader;
        }
        $current = static::$_loader;
        static::$_loader = $loader;

        $success = spl_autoload_register(static::$_loader);
        if ($current) {
            spl_autoload_unregister($current);
        }
        return $success;
    }

    /**
     * Restore the original autoloader behavior.
     */
    public static function unpatch()
    {
        if (!static::$_loader) {
            return false;
        }

        spl_autoload_register(static::$_original);
        $success = spl_autoload_unregister(static::$_loader);

        static::$_loader = null;
        return $success;
    }

    /**
     * Return the patchers container.
     *
     * @return mixed
     */
    public function patchers()
    {
        return $this->_patchers;
    }

    /**
     * Loads the given class or interface.
     *
     * @param  string $class The name of the class.
     * @return boolean|null Returns `true` if loaded, `null` otherwise.
     */
    public function loadClass($class)
    {
        if (!$file = $this->findFile($class)) {
            return;
        }
        if (!$this->patchable($class)) {
            include $file;
            return true;
        }
        return $this->loadFile($file);
    }

    /**
     * Check if a class can be patched or not.
     *
     * @param  string $class The name of the class to check.
     * @return boolean Returns `true` if the class can be patched, `false` otherwise.
     */
    public function patchable($class)
    {
        foreach ($this->_exclude as $namespace) {
            if (strpos($class, $namespace) === 0) {
                return false;
            }
        }
        if ($this->_include === ['*']) {
            return true;
        }
        foreach ($this->_include as $namespace) {
            if (strpos($class, $namespace) === 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * Loads a file.
     *
     * @param  string $class The name of the class.
     * @return bool|null Returns `true` if loaded, null otherwise.
     */
    public function loadFile($file)
    {
        if ($this->_persistent && $path = $this->cache($file)) {
            require $path;
            return true;
        }
        $code = file_get_contents($file);
        $rewrite = $this->_patchers ? $this->_patchers->process($code, $file) : $code;
        if ($rewrite) {
            if ($this->_cache) {
                require $this->cache($file, $rewrite);
            } else {
                throw new Exception('Cache path required');
            }
            return true;
        }
    }

    /**
     * Cache helper.
     *
     * @param  string $file The source file path.
     * @param  string $content The patched content to cache.
     * @return string The patched file path or the cache path if called with no params.
     */
    public function cache($file = null, $content = null)
    {
        if ($file === null && $content === null) {
            return $this->_cache;
        }
        $path = $this->_cache . DS . ltrim($file, DS);
        if ($content === null) {
            if ($this->_cache && file_exists($path) && (filemtime($path) > filemtime($file))) {
                return $path;
            }
            return false;
        }
        if (!file_exists(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }
        file_put_contents($path, $content);
        return $path;
    }

    /**
     * Finds the path to the file where the class is defined.
     *
     * @param string $class The name of the class
     * @return string|false The path if found, false otherwise
     */
    public function findFile($class)
    {
        $findFile = $this->_findFile;
        $file = static::originalInstance()->$findFile($class);
        if ($this->_patchers) {
            return $this->_patchers->findFile($this, $class, $file);
        }
        return $file;
    }

    /**
     * Returns the class map array.
     *
     * @return array
     */
    public function getClassMap()
    {
        $getClassMap = $this->_getClassMap;
        return static::originalInstance()->$getClassMap($class);
    }

    /**
     * Returns the class map array.
     *
     * @return array
     */
    public function getPrefixes()
    {
        $getPrefixes = $this->_getPrefixes;
        return static::originalInstance()->$getPrefixes();
    }

    /**
     * Returns the class map array.
     *
     * @return array
     */
    public function getPrefixesPsr4()
    {
        $getPrefixesPsr4 = $this->_getPrefixesPsr4;
        return static::originalInstance()->$getPrefixesPsr4();
    }

    public function getPaths() {
        $ds = DIRECTORY_SEPARATOR;
        $paths = static::getPrefixesPsr4();
        foreach (static::getPrefixes() as $namespace => $dirs) {
            foreach ($dirs as $key => $dir) {
                $paths[$namespace][$key] = $dir . $ds . trim(strtr($namespace, '\\', $ds), $ds);
            }
        }
        return $paths;
    }

    /**
     * Returns the path of a namespace.
     *
     * @return string
     */
    public function findPath($namespace)
    {
        $loader = static::originalInstance();

        $paths = static::getPaths();
        $logicalPath = trim(strtr($namespace, '\\', DIRECTORY_SEPARATOR), DIRECTORY_SEPARATOR);

        foreach ($paths as $prefix => $dirs) {
            if (strpos($namespace, $prefix) === 0) {
                foreach ($dirs as $dir) {
                    $path = $dir . DIRECTORY_SEPARATOR . substr($logicalPath, strlen($prefix));
                    if (is_dir($path)) {
                        return $path;
                    }
                    if (file_exists($file = $path . '.php')) {
                        return $file ;
                    }
                    if (file_exists($file = $path . '.hh')) {
                        return $file;
                    }
                }
            }
        }
    }
}
