<?php
namespace kahlan\plugin;

class DummyClass
{
    /**
     * Enable/disable the dummy class feature.
     *
     * @var boolean
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
     * Clear class to default values.
     */
    public static function clear()
    {
        static::$_enabled = true;
    }
}
