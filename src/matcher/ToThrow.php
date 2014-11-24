<?php
namespace kahlan\matcher;

use Exception;

class ToThrow
{
    /**
     * Expect that `$actual` throws the `$expected` exception.
     *
     * The value passed to `$expected` is either an exception or the expected exception's message.
     *
     * @param  Closure $actual The closure to run.
     * @param  mixed   $expected A string indicating what the error text is expected to be or a
     *                 exception instance.
     * @return boolean
     */
    public static function match($actual, $expected = null, $code = 0)
    {
        $exception = static::expected($expected, $code);

        if (!$e = static::actual($actual)) {
            return false;
        }

        return static::_matchException($e, $exception);
    }

    /**
     * Compare if two exception are similar.
     *
     * @param  string  $actual   The actual message.
     * @param  string  $expected The expected message.
     * @return boolean
     */
    public static function _matchException($actual, $exception)
    {
        if ($exception instanceof AnyException) {
            $code = $exception->getCode() ? $actual->getCode() : $exception->getCode();
            $class = get_class($actual);
        } else {
            $code = $actual->getCode();
            $class = get_class($exception);
        }

        if (get_class($actual) !== $class) {
            return false;
        }

        $sameCode = $code === $exception->getCode();
        $sameMessage = static::_sameMessage($actual->getMessage(), $exception->getMessage());
        return $sameCode && $sameMessage;
    }

    /**
     * Compare if exception messages are similar.
     *
     * @param  string  $actual   The actual message.
     * @param  string  $expected The expected message.
     * @return boolean
     */
    public static function _sameMessage($actual, $expected)
    {
        if (preg_match('~^(?P<char>\~|/|@|#).*?(?P=char)$~', $expected)) {
            $same = preg_match($expected, $actual);
        } else {
            $same = $actual === $expected;
        }
        return $same || !$expected;
    }

    /**
     * Normalize the actual value as an Exception.
     *
     * @param  mixed $actual The actual value to be normalized.
     * @return mixed The normalized value.
     */
    public static function actual($actual)
    {
        try {
            $actual();
        } catch (Exception $e) {
            return $e;
        }
    }

    /**
     * Normalize the expected value as an Exception.
     *
     * @param  mixed $expected The expected value to be normalized.
     * @return mixed The normalized value.
     */
    public static function expected($expected, $code = 0)
    {
        if ($expected === null || is_string($expected)) {
            return new AnyException($expected, $code);
        }
        return $expected;
    }

    public static function description($report)
    {
        $description = "throw a compatible exception.";
        $params['actual'] = static::actual($report['params']['actual']);
        $params['expected'] = static::expected($report['params']['expected'], $report['params']['code']);
        return compact('description', 'params');
    }
}
