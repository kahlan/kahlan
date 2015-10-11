<?php
namespace kahlan\plugin;

use kahlan\Suite;
use kahlan\plugin\call\Message;

class Call
{
    /**
     * Logged calls.
     *
     * @var array
     */
    protected static $_logs = [];

    /**
     * Current index of logged calls per reference.
     *
     * @var array
     */
    protected static $_index = 0;

    /**
     * Message invocation.
     *
     * @var array
     */
    protected $_message = [];

    /**
     * Reference.
     *
     * @var array
     */
    protected $_reference = null;

    /**
     * The Constructor.
     *
     * @param object|string $reference An instance or a fully-namespaced class name.
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
     * Enable logging for the passed method name.
     *
     * @param string $name The method name.
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
     * Logs a call.
     *
     * @param mixed  $reference An instance or a fully-namespaced class name or an array of them.
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

    /**
     * Helper for the `log()` method.
     *
     * @param object|string $reference An instance or a fully-namespaced class name.
     * @param string $call             The method name.
     */
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
     * Returns Logged calls.
     */
    public static function logs()
    {
        return static::$_logs;
    }

    /**
     * Gets/sets the find index
     *
     * @param  integer $index The index value to set or `null` to get the current one.
     * @return integer        Return founded log call.
     */
    public static function lastFindIndex($index = null)
    {
        if ($index !== null) {
            static::$_index = $index;
        }
        return static::$_index;
    }

    /**
     * Finds a logged call.
     *
     * @param  object|string $reference An instance or a fully-namespaced class name.
     * @param  string        $method    The method name.
     * @param  interger      $index     Start index.
     * @return array|false              Return founded log call.
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

    /**
     * Helper for the `find()` method.
     *
     * @param  object|string $reference An instance or a fully-namespaced class name.
     * @param  interger      $index     Start index.
     * @return array                    The founded log calls.
     */
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

    /**
     * Helper for the `_findAll()` method.
     *
     * @param  object|string $reference An instance or a fully-namespaced class name.
     * @param  array         $logs      The logged calls.
     * @return array                    The founded log call.
     */
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
     * Clears the registered references & logs.
     */
    public static function reset()
    {
        static::$_logs = [];
        static::$_index = 0;
        Suite::reset();
    }
}
