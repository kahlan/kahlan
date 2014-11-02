<?php
namespace kahlan\plugin;

class DummyClass
{
    /**
     * Enable/disable the dummy class feature.
     *
     * @var boolean
     */
    protected static $_disabled = false;

    /**
     * Return the status of the quit statements.
     *
     * @return boolean $active
     */
    public static function disabled()
    {
        return static::$_disabled;
    }

    /**
     * Enabled/Disable the `exit`, `die` statements.
     *
     * @param boolean $active
     */
    public static function disable($disable = true)
    {
        static::$_disabled = $disable;
    }

    /**
     * Clear class to default values.
     */
    public static function clear()
    {
        static::$_disabled = false;
    }
}
