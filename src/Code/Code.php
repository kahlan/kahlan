<?php declare(strict_types=1);

namespace Kahlan\Code;

use Throwable;
use Exception;

class Code
{
    /**
     * Executes a callable until a timeout is reached or the callable returns `true`.
     *
     * @param callable $callable The callable to execute.
     * @param integer $timeout The timeout value.
     *
     * @return mixed
     * @throws \Throwable
     */
    public static function run(callable $callable, int $timeout = 0)
    {
        if (!function_exists('pcntl_signal')) {
            throw new Exception("PCNTL threading is not supported by your system.");
        }

        pcntl_signal(SIGALRM, function () use ($timeout) {
            throw new TimeoutException("Timeout reached, execution aborted after {$timeout} second(s).");
        }, true);

        pcntl_alarm($timeout);

        $result = null;

        try {
            $result = $callable();
            pcntl_alarm(0);
        } catch (Throwable $e) {
            pcntl_alarm(0);
            throw $e;
        }

        return $result;
    }

    /**
     * Executes a callable in a loop until a timeout is reached or the callable returns `true`.
     *
     * @param callable $callable The callable to execute.
     * @param int $timeout The timeout value.
     * @param int $delay The delay value
     *
     * @return mixed
     * @throws \Throwable
     */
    public static function spin(callable $callable, int $timeout = 0, int $delay = 100000)
    {
        $closure = function () use ($callable, $timeout, $delay) {
            $timeout = (float) $timeout;
            $start = microtime(true);

            do {
                if ($result = $callable()) {
                    return $result;
                }
                usleep($delay);
                $current = microtime(true);
            } while ($current - $start < $timeout);

            throw new TimeoutException("Timeout reached, execution aborted after {$timeout} second(s).");
        };

        if (!function_exists('pcntl_signal')) {
            return $closure();
        }
        return static::run($closure, $timeout);
    }
}
