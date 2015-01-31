<?php
namespace kahlan\matcher;

class ToEcho
{
    /**
     * Runs the actual closure.
     *
     * @param  Closure $actual The actual closure.
     * @return string          The string result.
     */
    public static function actual($actual)
    {
        ob_start();
        $actual();
        $output = ob_get_contents();
        ob_end_clean();
        return $output;
    }
    /**
     * Expect that `$actual` echo the `$expected` string.
     *
     * @param  Closure $actual The closure to run.
     * @param  mixed   $expected The output string.
     * @return boolean
     */
    public static function match($actual, $expected = null)
    {
        return static::actual($actual) === $expected;
    }

    public static function description($report)
    {
        $description = "echo the expected string.";
        $params['actual'] = static::actual($report['params']['actual']);
        $params['expected'] = $report['params']['expected'];
        return compact('description', 'params');
    }
}
