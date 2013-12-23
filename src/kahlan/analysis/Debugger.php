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
class Debugger {

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
	public function config($options = []) {
		$defaults = ['classes' => []];
		$options += $defaults;
		static::$_classes += $options['classes'];
	}

	/**
	 * Get a backtrace string based on the supplied options.
	 *
	 * @param array $options Format for outputting stack trace. Available options are:
 	 *        - `'start'`: The depth to start with.
	 *        - `'depth'`: The maximum depth of the trace.
	 *        - `'message'`: Either `null` for default message or a string.
	 *        - `'trace'`: A trace to use instead of generating one.
	 * @return string The Backtrace formatted according to `'format'` option.
	 */
	public static function trace($options = []) {
		$defaults = ['message' => null, 'trace' => []];
		$options += $defaults;
		$backtrace = static::backtrace($options);
		$back = [];
		$trace = static::normalize($options['trace'] ?: debug_backtrace());
		$error = reset($trace);

		$message = '';
		if ($options['message'] === null && isset($error['code'])) {
			$message = "Code({$error['code']}): {$error['message']}\n";
		} elseif($options['message']) {
			$message = $options['message'] . "\n";
		}

		foreach ($backtrace as $trace) {
			$string = $trace['function'];
			if ($trace['definition'] !== '?') {
				$string .= '@'. $trace['definition'];
			}
			$back[] = $string .' - ' . $trace['file'] . ', line ' . $trace['line'];
		}
		return $message . join("\n", $back);
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
	public static function backtrace($options = []) {
		$defaults = [
			'trace' => [],
			'start' => 0,
			'depth' => 0,
			'definition' => true
		];
		$options += $defaults;

		$backtrace = static::normalize($options['trace'] ?: debug_backtrace());
		$error = reset($backtrace);

		$traceDefault = [
			'line' => '?',
			'file' => '[internal]',
			'class' => null,
			'function' => '[main]',
			'definition' => '?'
		];

		$loader = static::loader();
		$back = [];

		foreach($backtrace as $i => $trace) {
			$trace += $traceDefault;

			if ($options['definition']) {
				$trace['definition'] = static::_closureDef($trace);
			}

			if (isset($backtrace[$i + 1])) {
				$next = $backtrace[$i + 1] + $traceDefault;
				$trace['function'] = $next['function'];

				if (!empty($next['class'])) {
					$trace['function'] = $next['class'] . '::' . $trace['function'] . '()';
				}
			}

			if (preg_match("/eval\(\)'d code/", $trace['file']) && $trace['class'] && $loader) {
				$trace['file'] = $loader->findFile($trace['class']);
			}

			if (in_array($trace['function'], ['call_user_func_array', 'trigger_error'])) {
				continue;
			}

			if (strpos($trace['function'], '{closure}') !== false) {
				$trace['function'] = "{closure}";
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

	public static function normalize($backtrace) {
		if ($backtrace instanceof Exception) {
			return array_merge([[
				'code' => $backtrace->getCode(),
				'message' => $backtrace->getMessage(),
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
	 * Locates original location of closures.
	 *
	 * @param mixed $path File path to inspect.
	 * @param integer $callLine Line number of class reference.
	 * @return mixed Returns the line number where the method called is defined.
	 */
	protected static function _definition($path, $callLine) {
		if (!file_exists($path)) {
			return;
		}
		foreach (array_reverse(token_get_all(file_get_contents($path))) as $token) {
			if (!is_array($token) || $token[2] > $callLine) {
				continue;
			}
			if ($token[0] === T_FUNCTION) {
				return $token[2];
			}
		}
	}

	/**
	 * Helper method for caching closure function references to help the process of building the
	 * stack trace.
	 * @param  array $frame Backtrace information.
	 * @return string Returns either the cached or the fetched closure function reference while
	 *                writing its reference to the cache array `$_closureCache`.
	 */
	protected static function _closureDef($frame) {
		$reference = '::';
		$frame += ['file' => '??', 'line' => '??'];
		$cacheKey = "{$frame['file']}@{$frame['line']}";

		if (isset(static::$_closureCache[$cacheKey])) {
			return static::$_closureCache[$cacheKey];
		}

		$reference = $frame['file'];
		$line = static::_definition($reference, $frame['line']) ?: '?';
		return static::$_closureCache[$cacheKey] = $line;
	}

	/**
	 * Get/set a compatible composer autoloader.
	 *
	 * @param  object|null $loader The autoloader to set or `null` to get the default one.
	 * @return object      The autoloader.
	 */
	public static function loader($loader = null) {
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

?>