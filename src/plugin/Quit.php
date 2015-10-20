<?php
namespace kahlan\plugin;

use kahlan\QuitException;

class Quit
{
    /**
     * Indicates if the `exit` or `die` statements are disabled or not.
     */
    protected static $_enabled = true;

    /**
     * Return the status of the quit statements.
     *
     * @return boolean $active
     */
    public static function enabled()
    {
        return static::$_enabled;
    }

    /**
     * Enabled the `exit`, `die` statements.
     */
    public static function enable()
    {
        static::$_enabled = true;
    }

    /**
     * Disable the `exit`, `die` statements.
     */
    public static function disable()
    {
        static::$_enabled = false;
    }

    /**
     * Run a controlled quit statement.
     *
     * @param  integer              $status Use 0 for a successful exit.
     * @throws kahlan\QuitException         Only if disableed is `true`.
     */
    public static function quit($status = 0)
    {
        if (static::enabled()) {
            exit($status);
        }
        throw new QuitException('Exit statement occurred', $status);
    }

    /**
     * Clear class to default values.
     */
    public static function reset()
    {
        static::$_enabled = true;
    }
}
