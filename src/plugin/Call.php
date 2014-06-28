<?php
/**
 * Kahlan: PHP Testing Framework
 *
 * @copyright     Copyright 2013, CrysaLEAD
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace kahlan\plugin;

use InvalidArgumentException;
use kahlan\util\String;
use kahlan\plugin\call\Message;

class Call
{
    /**
     * [Optimisation Concern] Cache all watched class
     *
     * @var array
     * @see kahlan\plugin\Call::watched()
     */
    protected static $_watched = [];

    /**
     * Logged calls
     *
     * @var array
     */
    protected static $_logs = [];

    /**
     * Current index of logged calls per reference
     *
     * @var array
     */
    protected static $_index = 0;

    /**
     * Message invocation
     *
     * @var array
     */
    protected $_message = [];

    /**
     * Reference
     *
     * @var array
     */
    protected $_reference = null;

    /**
     * Constructor
     *
     * @param mixed $reference An instance or a fully namespaced class name.
     */
    public function __construct($reference, $static = false)
    {
        $this->_reference = $reference;
        if (is_object($reference)) {
            static::$_watched[get_class($reference)] = $this;
        }
        static::$_watched[String::hash($reference)] = $this;
    }

    /**
     * Active method call logging for a method
     *
     * @param string $name method name.
     */
    public function method($name)
    {
        $static = false;
        if (preg_match('/^::.*/', $name)) {
            $static = true;
            $name = substr($name, 2);
        }
        return $this->_message = new Message([
            'reference' => $this->_reference,
            'static' => $static,
            'name' => $name
        ]);
    }

    /**
     * Log a call.
     *
     * @param mixed  $reference An instance or a fully namespaced class name.
     * @param string $call      The method name.
     */
    public static function log($reference, $call)
    {
        $hash = String::hash($reference);
        $static = false;
        if (preg_match('/^::.*/', $call['name'])) {
            $call['name'] = substr($call['name'], 2);
            $call['static'] = true;
        }
        if (is_object($reference)) {
            $call += ['instance' => $reference, 'class' => get_class($reference), 'static' => $static];
        } else {
            $call += ['instance' => null, 'class' => $reference, 'static' => $static];
        }
        static::$_logs[] = $call;
    }

    /**
     * Return Logged calls.
     */
    public static function logs()
    {
        return static::$_logs;
    }

    /**
     * Find a logged call.
     *
     * @param  mixed      $reference An instance or a fully namespaced class name.
     * @param  string     $method    The method name.
     * @param  boolean    $reset     If `true` start finding from the start of the logs.
     * @return array|null Return founded log call.
     */
    public static function find($reference, $call = null, $reset = true)
    {
        if ($reset) {
            static::$_index = 0;
        }
        if ($call === null) {
            return static::_findAll($reference);
        }
        $index = static::$_index;
        $count = count(static::$_logs);
        for ($i = $index; $i < $count; $i++) {
            $log = static::$_logs[$i];
            if (!static::_matchReference($reference, $log)) {
                continue;
            }

            if ($call->match($log)) {
                static::$_index = $i + 1;
                return $log;
            }
        }
        return false;
    }

    protected static function _findAll($reference)
    {
        $result = [];
        $index = static::$_index;
        $count = count(static::$_logs);
        for ($i = $index; $i < $count; $i++) {
            $log = static::$_logs[$i];
            if (static::_matchReference($reference, $log)) {
                $result[] = $log;
            }
        }
        return $result;
    }

    protected static function _matchReference($reference, $log)
    {
        if (is_object($reference)) {
            if ($reference !== $log['instance']) {
                return false;
            }
        } elseif ($reference !== $log['class']) {
            return false;
        }
        return true;
    }

    /**
     * [Optimisation Concern] Check if a specific class is watched
     *
     * @param  string         $class A fully namespaced class name.
     * @return boolean|array
     */
    public static function watched($class = null)
    {
        if ($class === null) {
            return array_keys(static::$_watched);
        }
        return isset(static::$_watched[$class]);
    }

    /**
     * Clear the registered references & logs.
     */
    public static function clear()
    {
        static::$_watched = [];
        static::$_logs = [];
        static::$_index = [];
    }
}
