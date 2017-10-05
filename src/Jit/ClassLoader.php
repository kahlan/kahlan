<?php
namespace Kahlan\Jit;

use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

class ClassLoader
{
    /**
     * Autoloaded files.
     */
    protected $_files = [];

    /**
     * Cache path. If `false` the caching is not enable.
     *
     * @var string
     */
    protected $_cachePath = false;

    /**
     * Additional watched files.
     *
     * @var integer
     */
    protected $_watched = [];

    /**
     * Most recent modification timestamps of the watched files.
     *
     * @var integer
     */
    protected $_watchedTimestamp = 0;

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
     * Overrided loader reference.
     *
     * @var object
     */
    protected static $_autoloader = null;

    /**
     * Apply JIT code patching
     *
     * @param array $options Options for the constructor.
     */
    public function patch($options = [])
    {
        $defaults = [
            'patchers'        => null,
            'exclude'         => [],
            'include'         => ['*'],
            'watch'           => [],
            'cachePath'       => rtrim(sys_get_temp_dir(), DS) . DS . 'jit',
            'clearCache'      => false
        ];
        $options += $defaults;

        $this->_patchers = new Patchers();
        $this->_cachePath = rtrim($options['cachePath'], DS);
        $this->_exclude = (array) $options['exclude'];
        $this->_exclude[] = 'jit\\';
        $this->_include = (array) $options['include'];

        if ($options['clearCache']) {
            $this->clearCache();
        }

        if ($options['watch']) {
            $this->watch($options['watch']);
        }
    }

    /**
     * Unapply JIT code patching
     *
     * @param array $options Options for the constructor.
     */
    public function unpatch()
    {
        $this->_patchers = null;
    }

    /**
     * Returns a `ClassLoader` instance.
     *
     * @return object|null
     */
    public static function instance()
    {
        $loaders = spl_autoload_functions();
        foreach ($loaders as $key => $loader) {
            if ($loader[0] instanceof static) {
                return $loader[0];
            }
        }
    }

    /**
     * Sets some file to watch.
     *
     * When a watched file is modified, any cached file are invalidated.
     *
     * @param $files The array of file paths to watch.
     */
    public function watch($files)
    {
        $files = (array) $files;

        foreach ($files as $file) {
            $path = realpath($file);
            $this->_watched[$path] = $path;
        }
        $this->refreshWatched();
    }

    /**
     * Unwatch a watched file
     *
     * @param $files The array of file paths to unwatch.
     */
    public function unwatch($files)
    {
        $files = (array) $files;

        foreach ($files as $file) {
            $path = realpath($file);
            unset($this->_watched[$path]);
        }
        $this->refreshWatched();
    }

    /**
     * Returns watched files
     *
     * @return array The array of wateched file paths.
     */
    public function watched()
    {
        return array_values($this->_watched);
    }

    /**
     * Refresh watched file timestamps
     */
    public function refreshWatched()
    {
        $timestamps = [0];
        foreach ($this->_watched as $path) {
            $timestamps[] = filemtime($path);
        }
        $this->_watchedTimestamp = max($timestamps);
    }

    /**
     * Returns the patchers container.
     *
     * @return mixed
     */
    public function patchers()
    {
        return $this->_patchers;
    }

    /**
     * Checks if a class can be patched or not.
     *
     * @param  string  $class The name of the class to check.
     * @return boolean        Returns `true` if the class need to be patched, `false` otherwise.
     */
    public function patchable($class)
    {
        if (!$this->allowed($class)) {
            return false;
        }
        return $this->patchers()->patchable($class);
    }

    /**
     * Checks if a class is allowed to be patched.
     *
     * @param  string  $class The name of the class to check.
     * @return boolean        Returns `true` if the class is allowed to be patched, `false` otherwise.
     */
    public function allowed($class)
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
     * @param  string       $file  The path of the file.
     * @return boolean             Returns `true` if loaded, null otherwise.
     * @throws JitException
     */
    public function loadFile($filepath)
    {
        $file = realpath($filepath);
        if ($file === false) {
            throw new JitException("Error, the file `'{$filepath}'` doesn't exist.");
        }
        if (!$this->_patchers) {
            require $file;
            return true;
        }

        if (!$cached = $this->cached($file)) {
            $code = file_get_contents($file);
            $timestamp = filemtime($file);

            $rewrited = $this->_patchers->process($code, $file);
            $cached = $this->cache($file, $rewrited, max($timestamp, $this->_watchedTimestamp) + 1);
        }
        $includePath = get_include_path();
        set_include_path($includePath ? $includePath . ':' . dirname($file) : dirname($file));
        require $cached;
        restore_include_path();
        return true;
    }

    /**
     * Manualy load files.
     *
     * @param array An array of files to load.
     */
    public function loadFiles($files)
    {
        $files = (array) $files;

        foreach ($files as $file) {
            $this->loadFile($file);
        }
        return true;
    }

    /**
     * Cache helper.
     *
     * @param  string $file    The source file path.
     * @param  string $content The patched content to cache.
     * @return string          The patched file path or the cache path if called with no params.
     */
    public function cache($file, $content, $timestamp = null)
    {
        if (!$cachePath = $this->cachePath()) {
            throw new JitException('Error, any cache path has been defined.');
        }
        $path = $cachePath . DS . ltrim(preg_replace('~:~', '', $file), DS);

        if (!@file_exists(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        if (@file_put_contents($path, $content) === false) {
            throw new JitException("Unable to create a cached file at `'{$file}'`.");
        }

        if ($timestamp) {
            touch($path, $timestamp);
        }
        return $path;
    }

    /**
     * Gets a cached file path.
     *
     * @param  string         $file The source file path.
     * @return string|boolean       The cached file path or `false` if the cached file is not valid
     *                              or is not cached.
     */
    public function cached($file)
    {
        if (!$cachePath = $this->cachePath()) {
            return false;
        }
        $path = $cachePath . DS . ltrim(preg_replace('~:~', '', $file), DS);

        if (!@file_exists($path)) {
            return false;
        }

        $timestamp = filemtime($path);
        if ($timestamp > filemtime($file) && $timestamp > $this->_watchedTimestamp) {
            return $path;
        }
        return false;
    }

    /**
     * Returns the cache path.
     *
     * @return string
     */
    public function cachePath()
    {
        return rtrim($this->_cachePath);
    }

    /**
     * Clear the cache.
     */
    public function clearCache()
    {
        $cachePath = $this->cachePath();

        if (!file_exists($cachePath)) {
            return;
        }

        $dir = new RecursiveDirectoryIterator($cachePath, RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new RecursiveIteratorIterator($dir, RecursiveIteratorIterator::CHILD_FIRST);

        foreach ($files as $file) {
            $path = $file->getRealPath();
            $file->isDir() ? rmdir($path) : unlink($path);
        }
    }

    /**
     * Returns the composer autoloaded files.
     *
     * @param  arrar $files The composer autoloaded files.
     * @return array
     */
    public function files($files = [])
    {
        if (!func_num_args()) {
            return $this->_files;
        }
        $this->_files = $files;
        return $this;
    }

    /**
     * Returns both PSR-0 & PSR-4 prefixes and related paths.
     *
     * @return array
     */
    public function prefixes()
    {
        $ds = DIRECTORY_SEPARATOR;
        $paths = $this->_prefixDirsPsr4;

        foreach ($this->getPrefixes() as $namespace => $dirs) {
            foreach ($dirs as $key => $dir) {
                $paths[$namespace][$key] = $dir . $ds . trim(strtr($namespace, '\\', $ds), $ds);
            }
        }
        return $paths;
    }

    /**
     * Returns the path of a namespace or fully namespaced class name.
     *
     * @param  string      $namespace A namespace.
     * @param  boolean     $forceDir  Only consider directories paths.
     * @return string|null            Returns the found path or `null` if not path is found.
     */
    public function findPath($namespace, $forceDir = false)
    {
        $paths = static::prefixes();
        $logicalPath = trim(strtr($namespace, '\\', DIRECTORY_SEPARATOR), DIRECTORY_SEPARATOR);

        foreach ($paths as $prefix => $dirs) {
            if (strpos($namespace, $prefix) !== 0) {
                continue;
            }
            foreach ($dirs as $dir) {
                $root = $dir . DIRECTORY_SEPARATOR . substr($logicalPath, strlen($prefix));

                if ($path = $this->_path($root, $forceDir)) {
                    return realpath($path);
                }
            }
        }
    }

    /**
     * Build full path according to a root path.
     *
     * @param  string      $path      A root path.
     * @param  boolean     $forceDir  Only consider directories paths.
     * @return string|null            Returns the found path or `null` if not path is found.
     */
    protected function _path($path, $forceDir)
    {
        if ($forceDir) {
            return is_dir($path) ? $path : null;
        }
        if (file_exists($file = $path . '.php')) {
            return $file ;
        }
        if (file_exists($file = $path . '.hh')) {
            return $file;
        }
        if (is_dir($path)) {
            return $path;
        }
    }

    /*
     * This file is part of Composer.
     *
     * (c) Nils Adermann <naderman@naderman.de>
     *     Jordi Boggiano <j.boggiano@seld.be>
     *
     * For the full copyright and license information, please view the LICENSE
     * file that was distributed with this source code.
     */

    /**
     * ClassLoader implements a PSR-0, PSR-4 and classmap class loader.
     *
     *     $loader = new \Composer\Autoload\ClassLoader();
     *
     *     // register classes with namespaces
     *     $loader->add('Symfony\Component', __DIR__.'/component');
     *     $loader->add('Symfony',           __DIR__.'/framework');
     *
     *     // activate the autoloader
     *     $loader->register();
     *
     *     // to enable searching the include path (eg. for PEAR packages)
     *     $loader->setUseIncludePath(true);
     *
     * In this example, if you try to use a class in the Symfony\Component
     * namespace or one of its children (Symfony\Component\Console for instance),
     * the autoloader will first look for the class under the component/
     * directory, and it will then fallback to the framework/ directory if not
     * found before giving up.
     *
     * This class is loosely based on the Symfony UniversalClassLoader.
     *
     * @author Fabien Potencier <fabien@symfony.com>
     * @author Jordi Boggiano <j.boggiano@seld.be>
     * @see    http://www.php-fig.org/psr/psr-0/
     * @see    http://www.php-fig.org/psr/psr-4/
     */

    // PSR-4
    protected $_prefixLengthsPsr4 = [];
    protected $_prefixDirsPsr4 = [];
    protected $_fallbackDirsPsr4 = [];

    // PSR-0
    protected $_prefixesPsr0 = [];
    protected $_fallbackDirsPsr0 = [];

    protected $_useIncludePath = false;
    protected $_classMap = [];
    protected $_classMapAuthoritative = false;
    protected $_missingClasses = [];
    protected $_apcuPrefix;

    public function getPrefixes()
    {
        if (!empty($this->_prefixesPsr0)) {
            return call_user_func_array('array_merge', $this->_prefixesPsr0);
        }

        return [];
    }

    public function getPrefixesPsr4()
    {
        return $this->_prefixDirsPsr4;
    }

    public function getFallbackDirs()
    {
        return $this->_fallbackDirsPsr0;
    }

    public function getFallbackDirsPsr4()
    {
        return $this->_fallbackDirsPsr4;
    }

    public function getClassMap()
    {
        return $this->_classMap;
    }

    /**
     * @param array $classMap Class to filename map
     */
    public function addClassMap(array $classMap)
    {
        if ($this->_classMap) {
            $this->_classMap = array_merge($this->_classMap, $classMap);
        } else {
            $this->_classMap = $classMap;
        }
    }

    /**
     * Registers a set of PSR-0 directories for a given prefix, either
     * appending or prepending to the ones previously set for this prefix.
     *
     * @param string       $prefix  The prefix
     * @param array|string $paths   The PSR-0 root directories
     * @param bool         $prepend Whether to prepend the directories
     */
    public function add($prefix, $paths, $prepend = false)
    {
        if (!$prefix) {
            if ($prepend) {
                $this->_fallbackDirsPsr0 = array_merge(
                    (array) $paths,
                    $this->_fallbackDirsPsr0
                );
            } else {
                $this->_fallbackDirsPsr0 = array_merge(
                    $this->_fallbackDirsPsr0,
                    (array) $paths
                );
            }

            return;
        }

        $first = $prefix[0];
        if (!isset($this->_prefixesPsr0[$first][$prefix])) {
            $this->_prefixesPsr0[$first][$prefix] = (array) $paths;

            return;
        }
        if ($prepend) {
            $this->_prefixesPsr0[$first][$prefix] = array_merge(
                (array) $paths,
                $this->_prefixesPsr0[$first][$prefix]
            );
        } else {
            $this->_prefixesPsr0[$first][$prefix] = array_merge(
                $this->_prefixesPsr0[$first][$prefix],
                (array) $paths
            );
        }
    }

    /**
     * Registers a set of PSR-4 directories for a given namespace, either
     * appending or prepending to the ones previously set for this namespace.
     *
     * @param string       $prefix  The prefix/namespace, with trailing '\\'
     * @param array|string $paths   The PSR-4 base directories
     * @param bool         $prepend Whether to prepend the directories
     *
     * @throws \InvalidArgumentException
     */
    public function addPsr4($prefix, $paths, $prepend = false)
    {
        if (!$prefix) {
            // Register directories for the root namespace.
            if ($prepend) {
                $this->_fallbackDirsPsr4 = array_merge(
                    (array) $paths,
                    $this->_fallbackDirsPsr4
                );
            } else {
                $this->_fallbackDirsPsr4 = array_merge(
                    $this->_fallbackDirsPsr4,
                    (array) $paths
                );
            }
        } elseif (!isset($this->_prefixDirsPsr4[$prefix])) {
            // Register directories for a new namespace.
            $length = strlen($prefix);
            if ('\\' !== $prefix[$length - 1]) {
                throw new \InvalidArgumentException("A non-empty PSR-4 prefix must end with a namespace separator.");
            }
            $this->_prefixLengthsPsr4[$prefix[0]][$prefix] = $length;
            $this->_prefixDirsPsr4[$prefix] = (array) $paths;
        } elseif ($prepend) {
            // Prepend directories for an already registered namespace.
            $this->_prefixDirsPsr4[$prefix] = array_merge(
                (array) $paths,
                $this->_prefixDirsPsr4[$prefix]
            );
        } else {
            // Append directories for an already registered namespace.
            $this->_prefixDirsPsr4[$prefix] = array_merge(
                $this->_prefixDirsPsr4[$prefix],
                (array) $paths
            );
        }
    }

    /**
     * Registers a set of PSR-0 directories for a given prefix,
     * replacing any others previously set for this prefix.
     *
     * @param string       $prefix The prefix
     * @param array|string $paths  The PSR-0 base directories
     */
    public function set($prefix, $paths)
    {
        if (!$prefix) {
            $this->_fallbackDirsPsr0 = (array) $paths;
        } else {
            $this->_prefixesPsr0[$prefix[0]][$prefix] = (array) $paths;
        }
    }

    /**
     * Registers a set of PSR-4 directories for a given namespace,
     * replacing any others previously set for this namespace.
     *
     * @param string       $prefix The prefix/namespace, with trailing '\\'
     * @param array|string $paths  The PSR-4 base directories
     *
     * @throws \InvalidArgumentException
     */
    public function setPsr4($prefix, $paths)
    {
        if (!$prefix) {
            $this->_fallbackDirsPsr4 = (array) $paths;
        } else {
            $length = strlen($prefix);
            if ('\\' !== $prefix[$length - 1]) {
                throw new \InvalidArgumentException("A non-empty PSR-4 prefix must end with a namespace separator.");
            }
            $this->_prefixLengthsPsr4[$prefix[0]][$prefix] = $length;
            $this->_prefixDirsPsr4[$prefix] = (array) $paths;
        }
    }

    /**
     * Turns on searching the include path for class files.
     *
     * @param bool $useIncludePath
     */
    public function setUseIncludePath($useIncludePath)
    {
        $this->_useIncludePath = $useIncludePath;
    }

    /**
     * Can be used to check if the autoloader uses the include path to check
     * for classes.
     *
     * @return bool
     */
    public function getUseIncludePath()
    {
        return $this->_useIncludePath;
    }

    /**
     * Turns off searching the prefix and fallback directories for classes
     * that have not been registered with the class map.
     *
     * @param bool $classMapAuthoritative
     */
    public function setClassMapAuthoritative($classMapAuthoritative)
    {
        $this->_classMapAuthoritative = $classMapAuthoritative;
    }

    /**
     * Should class lookup fail if not found in the current class map?
     *
     * @return bool
     */
    public function isClassMapAuthoritative()
    {
        return $this->_classMapAuthoritative;
    }

    /**
     * APCu prefix to use to cache found/not-found classes, if the extension is enabled.
     *
     * @param string|null $apcuPrefix
     */
    public function setApcuPrefix($apcuPrefix)
    {
        $this->_apcuPrefix = function_exists('apcu_fetch') && ini_get('apc.enabled') ? $apcuPrefix : null;
    }

    /**
     * The APCu prefix in use, or null if APCu caching is not enabled.
     *
     * @return string|null
     */
    public function getApcuPrefix()
    {
        return $this->_apcuPrefix;
    }

    /**
     * Registers this instance as an autoloader.
     *
     * @param bool $prepend Whether to prepend the autoloader or not
     */
    public function register($prepend = false)
    {
        spl_autoload_register([$this, 'loadClass'], true, $prepend);
    }

    /**
     * Unregisters this instance as an autoloader.
     */
    public function unregister()
    {
        spl_autoload_unregister([$this, 'loadClass']);
    }

    /**
     * Loads the given class or interface.
     *
     * @param  string       $class The name of the class.
     * @return boolean|null        Returns `true` if loaded, `null` otherwise.
     */
    public function loadClass($class)
    {
        if (!$file = $this->findFile($class)) {
            return;
        }

        if (!$this->_patchers || !$this->patchable($class)) {
            includeFile($file);
            return true;
        }
        return $this->loadFile($file);
    }

    /**
     * Finds the path to the file where the class is defined.
     *
     * @param  string       $class The name of the class
     * @return string|false        The path if found, false otherwise
     */
    public function findFile($class)
    {
        // class map lookup
        if (isset($this->_classMap[$class])) {
            return $this->_classMap[$class];
        }
        if ($this->_classMapAuthoritative || isset($this->_missingClasses[$class])) {
            return false;
        }
        if ($this->_apcuPrefix !== null) {
            $file = apcu_fetch($this->_apcuPrefix . $class, $hit);
            if ($hit) {
                return $file;
            }
        }

        $file = $this->_findFileWithExtension($class, '.php');

        // Search for Hack files if we are running on HHVM
        if (!$file && defined('HHVM_VERSION')) {
            $file = $this->_findFileWithExtension($class, '.hh');
        }

        if ($this->_apcuPrefix !== null) {
            apcu_add($this->_apcuPrefix . $class, $file);
        }

        if ($file !== false) {
            $file = realpath($file);
        } else {
            // Remember that this class does not exist.
            $this->_missingClasses[$class] = true;
        }
        return $this->_patchers ? $this->_patchers->findFile($this, $class, $file) : $file;
    }

    protected function _findFileWithExtension($class, $ext)
    {
        // PSR-4 lookup
        $logicalPathPsr4 = strtr($class, '\\', DIRECTORY_SEPARATOR) . $ext;

        $first = $class[0];
        if (isset($this->_prefixLengthsPsr4[$first])) {
            foreach ($this->_prefixLengthsPsr4[$first] as $prefix => $length) {
                if (0 === strpos($class, $prefix)) {
                    foreach ($this->_prefixDirsPsr4[$prefix] as $dir) {
                        if (file_exists($file = $dir . DIRECTORY_SEPARATOR . substr($logicalPathPsr4, $length))) {
                            return $file;
                        }
                    }
                }
            }
        }

        // PSR-4 fallback dirs
        foreach ($this->_fallbackDirsPsr4 as $dir) {
            if (file_exists($file = $dir . DIRECTORY_SEPARATOR . $logicalPathPsr4)) {
                return $file;
            }
        }

        // PSR-0 lookup
        if (false !== $pos = strrpos($class, '\\')) {
            // namespaced class name
            $logicalPathPsr0 = substr($logicalPathPsr4, 0, $pos + 1)
                . strtr(substr($logicalPathPsr4, $pos + 1), '_', DIRECTORY_SEPARATOR);
        } else {
            // PEAR-like class name
            $logicalPathPsr0 = strtr($class, '_', DIRECTORY_SEPARATOR) . $ext;
        }

        if (isset($this->_prefixesPsr0[$first])) {
            foreach ($this->_prefixesPsr0[$first] as $prefix => $dirs) {
                if (0 === strpos($class, $prefix)) {
                    foreach ($dirs as $dir) {
                        if (file_exists($file = $dir . DIRECTORY_SEPARATOR . $logicalPathPsr0)) {
                            return $file;
                        }
                    }
                }
            }
        }

        // PSR-0 fallback dirs
        foreach ($this->_fallbackDirsPsr0 as $dir) {
            if (file_exists($file = $dir . DIRECTORY_SEPARATOR . $logicalPathPsr0)) {
                return $file;
            }
        }

        // PSR-0 include paths.
        if ($this->_useIncludePath && $file = stream_resolve_include_path($logicalPathPsr0)) {
            return $file;
        }

        return false;
    }
}

/**
 * Scope isolated include.
 *
 * Prevents access to $this/self from included files.
 */
function includeFile($file)
{
    include $file;
}
