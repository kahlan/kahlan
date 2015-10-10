<?php
namespace kahlan\util;

use Closure;
use Exception;
use InvalidArgumentException;

class Timeout
{
    public static function run($closure, $timeout = 0)
    {
        if (!is_callable($closure)) {
            throw new InvalidArgumentException();
        }

        $timeout = (integer) $timeout;

        pcntl_signal(SIGALRM, function($signal) use ($timeout) {
            throw new Exception("Timeout reached, execution aborted after {$timeout} second(s).");
        }, true);

        pcntl_alarm($timeout);

        try {
            $result = $closure();
        } catch (Exception $e) {
            throw $e;
        } finally {
            pcntl_alarm(0);
        }

        return $result;
    }

    public static function spin($closure, $timeout = 0)
    {
        if (!is_callable($closure)) {
            throw new InvalidArgumentException();
        }

        $timeout = (float) $timeout;
        $result = false;
        $start = microtime(true);

        do {
            try {
                if (($result = $closure()) !== null) {
                    return $result;
                }
            } catch (Exception $e) {}
            $current = microtime(true);

        } while ($current - $start < $timeout);

        throw new Exception("Timeout reached, execution aborted after {$timeout} second(s).");
    }
}