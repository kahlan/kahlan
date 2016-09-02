<?php
namespace Kahlan\Plugin\Call;

use Kahlan\Suite;
use Kahlan\Plugin\Monkey;
use Kahlan\Plugin\Call\Message;

class FunctionCalls
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
     * Logs a call.
     *
     * @param mixed  $reference A fully-namespaced function name.
     * @param string $args      The arguments.
     */
    public static function log($reference, $args)
    {
        static::$_logs[] = [
            'name'   => $reference,
            'args' => $args
        ];
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
     * @param  object        $message   The function message.
     * @param  interger      $index     Start index.
     * @return array|false              Return founded log call.
     */
    public static function find($message, $index = 0, $times = 0)
    {
        $success = false;
        $args = [];

        $count = count(static::$_logs);

        for ($i = $index; $i < $count; $i++) {
            $log = static::$_logs[$i];

            if (!$message->match($log, false)) {
                continue;
            }
            $args[] = $log['args'];

            if (!$message->matchArgs($log['args'])) {
                continue;
            }

            $times -= 1;
            if ($times < 0) {
                static::$_index = $i + 1;
                $success = true;
                break;
            } elseif ($times === 0) {
                $next = static::find($message, $i + 1);
                if ($next['success']) {
                    $args = array_merge($args, $next['args']);
                    $success = false;
                } else {
                    $success = true;
                    static::$_index = $i + 1;
                }
                break;
            }
        }
        $index = static::$_index;
        return compact('success', 'args', 'index');
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
