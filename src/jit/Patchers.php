<?php
/**
 * Kahlan: PHP Testing Framework
 *
 * @copyright     Copyright 2013, CrysaLEAD
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace kahlan\jit;

use BadMethodCallException;

/**
 * Patcher manager
 */
class Patchers {

    /**
     * Class dependencies.
     *
     * @var array
     */
    protected $_classes = [
        'parser' => 'kahlan\analysis\Parser',
    ];

    /**
     * The registered patchers.
     */
    protected $_patchers = [];

    /**
     * Add a patcher.
     *
     * @param  string         $name    The patcher name.
     * @param  object         $patcher A patcher.
     * @return object|boolean The added patcher instance or `false` on failure.
     */
    public function add($name, $patcher)
    {
        if (!is_object($patcher)) {
            return false;
        }
        return $this->_patchers[$name] = $patcher;
    }

    /**
     * Get a patcher.
     *
     * @param  string|object  $patcher A patcher class name or an intance.
     * @return object|boolean The patcher instance or `false` if not founded.
     */
    public function get($name)
    {
        if (isset($this->_patchers[$name])) {
            return $this->_patchers[$name];
        }
    }

    /**
     * Checks if a patcher exist.
     *
     * @param  string $name The patcher name.
     * @return boolean
     */
    public function exists($name)
    {
        return isset($this->_patchers[$name]);
    }

    /**
     * Removes a patcher.
     *
     * @param string $name The patcher name.
     */
    public function remove($name)
    {
        unset($this->_patchers[$name]);
    }

    /**
     * Removes all patchers.
     *
     * @param string $name The patcher name.
     */
    public function clear()
    {
        $this->_patchers = [];
    }

    /**
     * Run file loader patchers.
     *
     * @param string $path The original path of the file.
     * @param string The patched file path to load.
     */
    public function findFile($loader, $class, $file)
    {
        foreach ($this->_patchers as $patcher) {
            $file = $patcher->findFile($loader, $class, $file);
        }
        return $file;
    }

    /**
     * Run file patchers.
     *
     * @param  string $code The source code to process.
     * @return string The patched source code.
     */
    public function process($code)
    {
        if (!$code) {
            return '';
        }
        $parser = $this->_classes['parser'];
        $nodes = $parser::parse($code);
        foreach ($this->_patchers as $patcher) {
            $patcher->process($nodes);
        }
        return $parser::unparse($nodes);
    }

    /**
     * Run backtrace patchers.
     *
     * @param  string $path The path of the file.
     * @return array  The modified debug backtrace.
     */
    public function processBacktrace($options, $backtrace)
    {
        foreach ($this->_patchers as $patcher) {
            $backtrace = $patcher->processBacktrace($options, $backtrace);
        }
        return $backtrace;
    }

}
