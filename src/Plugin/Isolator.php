<?php
namespace Kahlan\Plugin;

use Kahlan\Jit\Interceptor;
use Kahlan\Jit\Patcher\Isolator as Patcher;

/**
 * Plugin that allows to isolate functions from given file.
 */
class Isolator
{
    /**
     * Performs functions isolation and requires them from given file.
     *
     * @param string $file   File path to isolate functions from.
     */
    public static function isolate($file)
    {
        $interceptor = Interceptor::instance();
        $interceptor->patchers()->add('isolator', new Patcher());
        $interceptor->loadFile($file);
        $interceptor->patchers()->remove('isolator');
    }
}
