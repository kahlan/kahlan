<?php
namespace kahlan\plugin;

class DummyClass
{
    /**
     * Indicates if the dummy class feature is enabled or not.
     *
     * @var boolean
     */
    protected static $_enabled = true;

    /**
     * Returns the status of the quit statements.
     *
     * @return boolean $active
     */
    public static function enabled()
    {
        return static::$_enabled;
    }

    /**
     * Enables the dummy classes.
     *
     */
    public static function enable()
    {
        static::$_enabled = true;
    }

    /**
     * Disables the dummy classes.
     */
    public static function disable()
    {
        static::$_enabled = false;
    }

    /**
     * Clears class to default values.
     */
    public static function reset()
    {
        static::$_enabled = true;
    }
}
