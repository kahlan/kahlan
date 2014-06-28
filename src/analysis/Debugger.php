<?php
/**
 * Kahlan: PHP Testing Framework
 *
 * @copyright     Copyright 2013, Union of RAD (http://union-of-rad.org), CrysaLEAD
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace kahlan\analysis;

use Exception;
use ReflectionClass;
use kahlan\util\String;

/**
 * The `Debugger` class provides basic facilities for generating and rendering meta-data about the
 * state of an application in its current context.
 */
class Debugger
{
    /**
     * Class dependencies.
     *
     * @var array
     */
    protected static $_classes = [
        'interceptor' => 'kahlan\jit\Interceptor'
    ];

    /**
     * Store the autoloader
     */
    public static $_loader = null;

    /**
     * Used for temporary closure caching.
     *
     * @see lithium\analysis\Debugger::_closureDef()
     * @var array
     */
    protected static $_closureCache = [];

    /**
     * Config method
     *
     * @param array $options Options config array.
     */
    public function config($options = [])
    {
        $defaults = ['classes' => []];
        $options += $defaults;
        static::$_classes += $options['classes'];
    }

    /**
     * Get a backtrace string based on the supplied options.
     *
     * @param  array $options Format for outputting stack trace. Available options are:
      *         - `'start'`: The depth to start with.
     *         - `'depth'`: The maximum depth of the trace.
     *         - `'message'`: Either `null` for default message or a string.
     *         - `'trace'`: A trace to use instead of generating one.
     * @return string The Backtrace formatted according to `'format'` option.
     */
    public static function trace($options = [])
    {
        $defaults = ['message' => null, 'trace' => []];
        $options += $defaults;
        $back = [];
        $backtrace = static::backtrace($options);
        $error = reset($backtrace);

        $message = '';
        if ($options['message'] === null && isset($error['code'])) {
            $message = "Code({$error['code']}): {$error['message']}\n";
        } elseif($options['message']) {
            $message = $options['message'] . "\n";
        }

        foreach ($backtrace as $trace) {
            $back[] =  static::_traceToString($trace);
        }
        return $message . join("\n", $back);
    }

    /**
     * Get a string representation of a trace.
     *
     * @param  array  $trace A trace array.
     * @return string The string representation of a trace.
     */
    protected static function _traceToString($trace)
    {
        $loader = static::loader();

        if (!empty($trace['class'])) {
            $trace['function'] = $trace['class'] . '::' . $trace['function'] . '()';
        } else {
            $line = static::_line($trace);
            $trace['line'] = $line !== $trace['line'] ? $line . ' to ' . $trace['line'] : $trace['line'];
        }

        if (preg_match("/eval\(\)'d code/", $trace['file']) && $trace['class'] && $loader) {
            $trace['file'] = $loader->findFile($trace['class']);
        }

        if (strpos($trace['function'], '{closure}') !== false) {
            $trace['function'] = "{closure}";
        }
        return $trace['function'] .' - ' . $trace['file'] . ', line ' . $trace['line'];
    }

    /**
     * Return a backtrace array based on the supplied options.
     *
     * @param array $options Format for outputting stack trace. Available options are:
      *        - `'start'`: The depth to start with.
     *        - `'depth'`: The maximum depth of the trace.
     *        - `'message'`: Either `null` for default message or a string.
     *        - `'trace'`: A trace to use instead of generating one.
     * @return array The backtrace array
     */
    public static function backtrace($options = [])
    {
        $defaults = [
            'trace' => [],
            'start' => 0,
            'depth' => 0
        ];
        $options += $defaults;

        $backtrace = static::normalize($options['trace'] ?: debug_backtrace());
        $error = reset($backtrace);

        $traceDefaults = [
            'line' => '?',
            'file' => '[internal]',
            'class' => null,
            'function' => '[main]'
        ];

        $back = [];
        $ignoreFunctions = ['call_user_func_array', 'trigger_error'];

        foreach($backtrace as $i => $trace) {
            $trace += $traceDefaults;
            if (strpos($trace['function'], '{closure}') !== false || in_array($trace['function'], $ignoreFunctions)) {
                continue;
            }
            $back[] = $trace;
        }

        $interceptor = static::$_classes['interceptor'];
        if ($patchers = $interceptor::instance()->patchers()) {
            $back = $patchers->processBacktrace($options, $back);
        }
        $count = count($back);
        return array_splice($back, $options['start'], $options['depth'] ?: $count);
    }

    public static function normalize($backtrace)
    {
        if ($backtrace instanceof Exception) {
            return array_merge([[
                'code' => $backtrace->getCode(),
                'message' => $backtrace->getMessage(),
                'function' => '',
                'file' => $backtrace->getFile(),
                'line' => $backtrace->getLine(),
                'args' => []
            ]], $backtrace->getTrace());
        } elseif (isset($backtrace['trace'])) {
            $trace = $backtrace['trace'];
            unset($backtrace['trace']);
            return array_merge([$backtrace], $trace);
        }
        return $backtrace;
    }

    /**
     * Locates original location of call from a trace.
     *
     * @param  array $trace A backtrace array.
     * @return mixed        Returns the line number where the method called is defined.
     */
    protected static function _line($trace)
    {
        $path = $trace['file'];
        $callLine = $trace['line'];
        if (!file_exists($path)) {
            return;
        }
        $file = file_get_contents($path);
        if (($i = static::_findPos($file, $callLine)) === null) {
            return;
        }
        $line = $callLine;

        $brackets = 0;
        while ($i >= 0) {
            if ($file[$i] === ')') {
                $brackets--;
            } elseif ($file[$i] === '(') {
                $brackets++;
            } elseif ($file[$i] === "\n") {
                $line--;
            }
            if ($brackets > 0) {
                return $line;
            }
            $i--;
        }
    }

    /**
     * Return the first character position of a specific line in a file.
     *
     * @param  string  $file     A file content.
     * @param  integer $callLine The number of line to find.
     * @return mixed             Returns the character position or null if not found.
     */
    protected static function _findPos($file, $callLine)
    {
        $len = strlen($file);
        $line = 1;
        $i = 0;
        while ($i < $len) {
            if ($file[$i] === "\n") {
                $line++;
            }
            if ($line === $callLine) {
                return $i;
            }
            $i++;
        }
    }

    /**
     * Get/set a compatible composer autoloader.
     *
     * @param  object|null $loader The autoloader to set or `null` to get the default one.
     * @return object      The autoloader.
     */
    public static function loader($loader = null)
    {
        if ($loader) {
            return static::$_loader = $loader;
        }
        if (static::$_loader !== null) {
            return static::$_loader;
        }
        $loaders = spl_autoload_functions();
        foreach ($loaders as $key => $loader) {
            if (is_array($loader) && method_exists($loader[0], 'findFile')) {
                return static::$_loader = $loader[0];
            }
        }
    }
}
