<?php
namespace kahlan\plugin;

use kahlan\Suite;
use kahlan\plugin\call\Message;

class Call
{
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
            Suite::register(get_class($reference));
        }
        Suite::register(Suite::hash($reference));
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
        $calls = [];
        if (is_array($reference)) {
            foreach ($reference as $value) {
                $calls[] = static::_call($value, $call);
            }
        } else {
            $calls[] = static::_call($reference, $call);
        }
        static::$_logs[] = $calls;
    }

    public static function _call($reference, $call) {
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
        return $call;
    }

    /**
     * Return Logged calls.
     */
    public static function logs()
    {
        return static::$_logs;
    }

    /**
     * Get set the find index
     *
     * @param  integer $index The index value to set or `null` to get the current one.
     * @return integer        Return founded log call.
     */
    public static function lastFindIndex($index = null) {
        if ($index !== null) {
            static::$_index = $index;
        }
        return static::$_index;
    }

    /**
     * Find a logged call.
     *
     * @param  mixed      $reference An instance or a fully namespaced class name.
     * @param  string     $method    The method name.
     * @param  interger   $index     Start index.
     * @return array|null Return founded log call.
     */
    public static function find($reference, $call = null, $index = 0)
    {
        if ($call === null) {
            return static::_findAll($reference, $index);
        }
        $count = count(static::$_logs);
        for ($i = $index; $i < $count; $i++) {
            $logs = static::$_logs[$i];
            if (!$log = static::_matchReference($reference, $logs)) {
                continue;
            }

            if ($call->match($log)) {
                static::$_index = $i + 1;
                return $log;
            }
        }
        return false;
    }

    protected static function _findAll($reference, $index)
    {
        $result = [];
        $count = count(static::$_logs);
        for ($i = $index; $i < $count; $i++) {
            $logs = static::$_logs[$i];
            if ($log = static::_matchReference($reference, $logs)) {
                $result[] = $log;
            }
        }
        return $result;
    }

    protected static function _matchReference($reference, $logs = [])
    {
        foreach ($logs as $log) {
            if (is_object($reference)) {
                if ($reference === $log['instance']) {
                    return $log;
                }
            } elseif ($reference === $log['class']) {
                return $log;
            }
        }
    }

    /**
     * Clear the registered references & logs.
     */
    public static function clear()
    {
        static::$_logs = [];
        static::$_index = 0;
    }
}
