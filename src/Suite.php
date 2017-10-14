<?php
namespace Kahlan;

use Closure;
use Throwable;
use RuntimeException;
use InvalidArgumentException;
use Kahlan\Analysis\Debugger;
use Kahlan\Block;
use Kahlan\Block\Group;
use Kahlan\Filter\Filters;

class Suite
{
    /**
     * The PHP constraint to respect
     *
     * @var string
     */
    public static $PHP = PHP_MAJOR_VERSION;

    /**
     * Blocks stack.
     *
     * @var Block[]
     */
    protected static $_blocks = [];

    /**
     * Store all hashed references.
     *
     * @var array
     */
    protected static $_registered = [];

    /**
     * The return status value (`0` for success).
     *
     * @var integer
     */
    protected $_status = 0;

    /**
     * The root group block of the test suite.
     *
     * @var Group
     */
    protected $_root = null;

    /**
     * Suite statistics.
     *
     * @var array
     */
    protected $_stats = null;

    /**
     * The reporters container.
     *
     * @var Reporters
     */
    protected $_reporters = null;

    /**
     * Array of fully-namespaced class name to clear on each `it()`.
     *
     * @var array
     */
    protected $_autoclear = [];

    /**
     * Set the number of fails allowed before aborting. `0` mean no fast fail.
     *
     * @see ::failfast()
     * @var integer
     */
    protected $_ff = 0;

    /**
     * Count the number of failure or exception.
     *
     * @see ::failfast()
     * @var integer
     */
    protected $_failures = 0;

    /**
     * The execution summary instance.
     *
     * @var object
     */
    protected $_summary = null;

    /**
     * A regexp pattern used to removes useless traces to focus on the one
     * related to a spec file.
     *
     * @var string
     */
    protected $_backtraceFocus = null;

    /**
     * List of specifications UUID.
     *
     * @var array
     */
    protected $_uuids = [];

    /**
     * The Constructor.
     */
    public function __construct()
    {
        $this->_summary = new Summary();
        $this->_root = new Group(['suite' => $this]);
    }

    /**
     * Get root block instance.
     *
     * @return object
     */
    public function root()
    {
        return $this->_root;
    }

    /**
     * Increment the number of failures.
     */
    public function failure()
    {
        $this->_failures++;
    }

    /**
     * Returns `true` if the suite reach the number of allowed failure by the fail-fast parameter.
     *
     * @return boolean;
     */
    public function failfast()
    {
        return $this->_ff && $this->_failures >= $this->_ff;
    }

    /**
     * Overrides the default error handler
     *
     * @param boolean $enable  If `true` override the default error handler,
     *                         if `false` restore the default handler.
     * @param array   $options An options array. Available options are:
     *                         - 'handler': An error handler closure.
     *
     */
    protected function _errorHandler($enable, $options = [])
    {
        $defaults = ['handler' => null];
        $options += $defaults;
        if (!$enable) {
            return restore_error_handler();
        }
        $handler = function ($code, $message, $file, $line = 0, $args = []) {
            if (error_reporting() === 0) {
                return;
            }
            $trace = debug_backtrace();
            $trace = array_slice($trace, 1, count($trace));
            $message = "`" . Debugger::errorType($code) . "` {$message}";
            $code = 0;
            $exception = compact('code', 'message', 'file', 'line', 'trace');
            throw new PhpErrorException($exception);
        };
        $options['handler'] = $options['handler'] ?: $handler;
        set_error_handler($options['handler'], error_reporting());
    }

    /**
     * Gets/sets the regexp pattern used to removes useless traces to focus on the one
     * related to a spec file.
     *
     * @param  string $pattern A wildcard pattern (i.e. `fnmatch()` style).
     * @return string          The focus regexp.
     */
    public function backtraceFocus($pattern = null)
    {
        if ($pattern === null) {
            return $this->_backtraceFocus;
        }
        $patterns = is_array($pattern) ? $pattern : [$pattern];
        foreach ($patterns as $key => $value) {
            $patterns[$key] = preg_quote($value, '~');
        }
        $pattern = join('|', $patterns);
        return $this->_backtraceFocus = strtr($pattern, ['\*' => '.*', '\?' => '.']);
    }

    /**
     * Runs all specs.
     *
     * @param  array     $options Run options.
     *
     * @return boolean            The result array.
     */
    public function run($options = [])
    {
        $defaults = [
            'reporters' => null,
            'autoclear' => [],
            'ff'        => 0
        ];
        $options += $defaults;

        $this->_reporters = $options['reporters'];
        $this->_autoclear = (array) $options['autoclear'];
        $this->_ff = $options['ff'];

        $this->report('start', ['total' => $this->active()]);

        $this->_errorHandler(true, $options);

        $passed = $this->root()->process();

        $this->_errorHandler(false);

        $this->summary()->memoryUsage(memory_get_peak_usage());

        $this->report('end', $this->summary());
        $this->_status = $passed ? 0 : -1;
        return $passed;
    }

    /**
     * Run a block's closure.
     *
     * @param  Block   $block   The block instance.
     * @param  Closure $closure The closure.
     * @param  string  $type    The closure type.
     * @return mixed
     */
    public function runBlock($block, $closure, $type)
    {
        return Filters::run($this, 'runBlock', [$block, $closure, $type], function ($next, $block, $closure, $type) {
            return call_user_func_array($closure, []);
        });
    }

    /**
     * Gets specs excecution results.
     *
     * @return array
     */
    public function summary()
    {
        return $this->_summary;
    }

    /**
     * Builds the suite.
     *
     * @return array The suite stats.
     */
    public function stats($block = null)
    {
        $block = $block ?: $this->root();
        static::push($block);
        if (Suite::$PHP >= 7 && !defined('HHVM_VERSION')) {
            try {
                $this->_stats = $this->_stats($block);
            } catch (Throwable $exception) {
                $block->log()->type('errored');
                $block->log()->exception($exception);

                $this->_stats = [
                    'normal' => 0,
                    'focused' => 0,
                    'excluded' => 0
                ];
            }
        } else {
            $this->_stats = $this->_stats($block);
        }
        static::pop();
        return $this->_stats;
    }

    /**
     * Builds the suite.
     *
     * @return array The suite stats.
     */
    protected function _stats($block)
    {
        $block->load();

        $normal = 0;
        $focused = 0;
        $excluded = 0;
        foreach ($block->children() as $child) {
            if ($block->excluded()) {
                $child->type('exclude');
            }
            if ($child instanceof Group) {
                $result = $this->stats($child);
                if ($child->focused() && !$result['focused']) {
                    $focused += $result['normal'];
                    $excluded += $result['excluded'];
                    $child->broadcastFocus();
                } else {
                    $normal += $result['normal'];
                    $focused += $result['focused'];
                    $excluded += $result['excluded'];
                }
            } else {
                switch ($child->type()) {
                    case 'exclude':
                        $excluded++;
                        break;
                    case 'focus':
                        $focused++;
                        break;
                    default:
                        $normal++;
                        break;
                }
                $this->_dispatch($child);
            }
        }
        return compact('normal', 'focused', 'excluded');
    }

    protected function _dispatch($spec)
    {
        $messages = $spec->messages();
        array_shift($messages);
        $message = trim(join(', ', $messages));
        $uuid = md5($message);
        if (isset($this->_uuids[$uuid])) {
            $spec->log()->type('errored');
            $spec->log()->exception(new RuntimeException("Error specifications must have a unique description message, duplicates of `'{$message}'` found."));
        }
        $this->_uuids[$uuid] = $spec;
    }

    /**
     * Gets number of total specs.
     *
     * @return integer
     */
    public function total()
    {
        if ($this->_stats === null) {
            $this->stats();
        }
        return $this->_stats['normal'] + $this->_stats['focused'] + $this->_stats['excluded'];
    }

    /**
     * Gets number of active specs.
     *
     * @return integer
     */
    public function active()
    {
        if ($this->_stats === null) {
            $this->stats();
        }
        return $this->root()->focused() ? $this->_stats['focused'] : $this->_stats['normal'];
    }

    /**
     * Gets exit status code according passed results.
     *
     * @param  integer $status If set force a specific status to be retruned.
     *
     * @return boolean         Returns `0` if no error occurred, `-1` otherwise.
     */
    public function status()
    {
        if ($this->root()->focused()) {
            return -1;
        }
        return $this->_status;
    }

    /**
     * Send some data to reporters.
     *
     * @param string $type The message type.
     * @param mixed  $data The message data.
     */
    public function report($type, $data)
    {
        if (!$reporters = $this->reporters()) {
            return;
        }
        $reporters->dispatch($type, $data);
    }

    /**
     * Gets the reporters container.
     *
     * @return object
     */
    public function reporters()
    {
        return $this->_reporters;
    }

    /**
     * Autoclears plugins.
     */
    public function autoclear()
    {
        foreach ($this->_autoclear as $plugin) {
            if (is_object($plugin)) {
                if (method_exists($plugin, 'clear')) {
                    $plugin->clear();
                }
            } elseif (method_exists($plugin, 'reset')) {
                $plugin::reset();
            }
        }
    }

    /**
     * Triggers the `stop` event.
     */
    public function stop()
    {
        $this->report('stop', $this->summary());
    }

    /**
     * Generates a hash from an instance or a string.
     *
     * @param  mixed $reference An instance or a fully namespaced class name.
     *
     * @return string           A string hash.
     * @throws InvalidArgumentException
     */
    public static function hash($reference)
    {
        if (is_object($reference)) {
            return spl_object_hash($reference);
        }
        if (is_string($reference)) {
            return $reference;
        }
        throw new InvalidArgumentException("Error, the passed argument is not hashable.");
    }

    /**
     * Registers a hash. [Mainly used for optimization]
     *
     * @param mixed $hash A hash to register.
     */
    public static function register($hash)
    {
        static::$_registered[$hash] = true;
    }

    /**
     * Gets registered hashes. [Mainly used for optimizations]
     *
     * @param  string     $hash The hash to look up. If none return all registered hashes.
     *
     * @return array|bool
     */
    public static function registered($hash = null)
    {
        if (!func_num_args()) {
            return static::$_registered;
        }

        return isset(static::$_registered[$hash]);
    }

    /**
     * Clears the registered hash.
     */
    public static function reset()
    {
        static::$_registered = [];
    }

    /**
     * Push a block in the stack.
     *
     * @param Block A block instance to push instance.
     */
    public static function push($block)
    {
        static::$_blocks[] = $block;
    }

    /**
     * Get the active scope instance.
     *
     * @return Scope The object instance or `null` if there's no active instance.
     */
    public static function current()
    {
        return end(static::$_blocks);
    }

    /**
     * Pop a block from the stack.
     *
     * @return Block The popped block instance .
     */
    public static function pop()
    {
        return array_pop(static::$_blocks);
    }
}
