<?php
/**
 * Kahlan: PHP Testing Framework
 *
 * @copyright     Copyright 2013, CrysaLEAD
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace kahlan\plugin;

class Pointcut {

    protected static $_classes = [
        'call' => 'kahlan\plugin\Call',
        'stub' => 'kahlan\plugin\Stub'
    ];

    /**
     * Point cut called before method execution.
     *
     * @return boolean If `true` is returned, the normal execution of the method is aborted.
     */
    public static function before($method, $self, &$params) {
        list($class, $name) = explode('::', $method);

        $call = static::$_classes['call'];
        $stub = static::$_classes['stub'];

        $lsb = is_object($self) ? get_class($self) : $self;
        $valid = $call::watched($lsb) || $call::watched($class) || $stub::stubbed($lsb) || $stub::stubbed($class);

        if (!$valid) {
            return false;
        }

        if ($name === '__call' || $name === '__callStatic') {
            $name = array_shift($params);
            $params = array_shift($params);
        }

        if (is_object($self)) {
            $list = $lsb === $class ? [$self, $lsb] : [$self, $lsb, $class];
        } else {
            $list = $lsb === $class ? [$lsb] : [$lsb, $class];
            $name = '::' . $name;
        }

        foreach ($list as $value) {
            $call::log($value, compact('name', 'params'));
        }

        if ($method = $stub::find($list, $name, $params)) {
            return $method;
        }

        return false;
    }

}

?>