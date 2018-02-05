<?php
namespace Kahlan;

use Closure;
use Exception;
use Throwable;
use Kahlan\SkipException;
use Kahlan\Suite;
use Kahlan\Log;
use Kahlan\Summary;
use Kahlan\Block\Specification;
use Kahlan\Block\Group;
use Kahlan\Analysis\Debugger;

abstract class Block
{

    /**
     * The block type.
     *
     * @var object
     */
    protected $_type = null;

    /**
     * The suite.
     *
     * @var object
     */
    protected $_suite = null;

    /**
     * The parent block.
     *
     * @var Scope
     */
    protected $_parent = null;

    /**
     * The spec message.
     *
     * @var string
     */
    protected $_message = null;

    /**
     * The timeout value.
     *
     * @var integer
     */
    protected $_timeout = 0;

    /**
     * The block scope.
     *
     * @var Scope
     */
    protected $_scope = null;

    /**
     * The block closure.
     *
     * @var Closure
     */
    protected $_closure = null;

    /**
     * Store the return value of the closure.
     *
     * @var mixed
     */
    protected $_return = null;

    /**
     * Stores the success value.
     *
     * @var boolean
     */
    protected $_passed = null;

    /**
     * The scope backtrace.
     *
     * @var array
     */
    protected $_backtrace = null;

    /**
     * The report log of executed spec.
     *
     * @var Log
     */
    protected $_log = null;

    /**
     * The execution summary instance.
     *
     * @var Summary
     */
    protected $_summary = null;

    /**
     * The Constructor.
     *
     * @param array $config The block config array. Options are:
     *                       -`'suite'`   _object_ : the suite instance.
     *                       -`'parent'`  _object_ : the parent block.
     *                       -`'type'`    _string_ : supported type are `'normal'` & `'focus'`.
     *                       -`'message'` _string_ : the description message.
     *                       -`'closure'` _Closure_: the closure of the test.
     *                       -`'log'`     _object_ : the log instance.
     *                       -`'timeout'` _integer_: the timeout.
     */
    public function __construct($config = [])
    {
        $defaults = [
            'suite'   => null,
            'parent'  => null,
            'type'    => 'normal',
            'message' => '',
            'closure' => null,
            'log'     => null,
            'timeout' => 0
        ];
        $config += $defaults;

        $this->_suite   = $config['suite'] ?: new Suite();
        $this->_parent  = $config['parent'];
        $this->_type    = $config['type'];
        $this->_message = $config['message'];
        $this->_closure = $config['closure'] ?: function () {
        };
        $this->_timeout = $config['timeout'];

        $suite = $this->suite();
        $this->_backtrace = Debugger::focus($suite->backtraceFocus(), Debugger::backtrace(), 1);

        $this->_log     = $config['log'] ?: new Log([
            'block' => $this,
            'backtrace' => $this->_backtrace
        ]);

        $this->_summary = $suite->summary();

        if ($this->_type === 'focus') {
            $this->_emitFocus();
        }
    }

    /**
     * Return the suite instance.
     *
     * @return Suite
     */
    public function suite()
    {
        return $this->_suite;
    }

    /**
     * Return the parent block.
     *
     * @return Scope
     */
    public function parent()
    {
        return $this->_parent;
    }

    /**
     * Return the spec's message.
     *
     * @return array
     */
    public function message()
    {
        return $this->_message;
    }

    /**
     * Return the block scope.
     *
     * @return array
     */
    public function scope()
    {
        return $this->_scope;
    }

    /**
     * Return the block closure.
     *
     * @return array
     */
    public function closure()
    {
        return $this->_closure;
    }

    /**
     * Checks if all test passed.
     *
     * @return boolean Returns `true` if no error occurred, `false` otherwise.
     */
    public function process(&$return = null)
    {
        if ($this->_passed === null) {
            $this->_process();
        }
        $return = $this->_return;
        return $this->_passed;
    }

    /**
     * Returns `true`/`false` if test passed or not, `false` if not and `null` if not runned.
     *
     * @return boolean.
     */
    public function passed()
    {
        return $this->_passed;
    }

    /**
     * Set/get the block type.
     *
     * @param  string $type The type mode.
     * @return mixed
     */
    public function type($type = null)
    {
        if (!func_num_args()) {
            return $this->_type;
        }
        $this->_type = $type;
        return $this;
    }

    /**
     * Check for excluded mode.
     *
     * @return boolean
     */
    public function excluded()
    {
        return $this->_type === 'exclude';
    }

    /**
     * Check for focused mode.
     *
     * @return boolean
     */
    public function focused()
    {
        return $this->_type === 'focus';
    }

    /**
     * Return all parent block instances.
     *
     * @param  boolean $current If `true` include `$this` to the list.
     * @return array.
     */
    public function parents($current = false)
    {
        $instances = [];
        $instance  = $current ? $this : $this->_parent;

        while ($instance !== null) {
            $instances[] = $instance;
            $instance = $instance->_parent;
        }
        return array_reverse($instances);
    }

    /**
     * Return all messages upon the root.
     *
     * @return array
     */
    public function messages()
    {
        $messages = [];
        $instances = $this->parents(true);
        foreach ($instances as $instance) {
            $messages[] = $instance->message();
        }
        return $messages;
    }

    /**
     * Get/set the timeout.
     *
     * @return integer
     */
    public function timeout($timeout = null)
    {
        if (func_num_args()) {
            $this->_timeout = $timeout;
        }
        return $this->_timeout;
    }

    /**
     * Return the backtrace array.
     *
     * @return array
     */
    public function backtrace()
    {
        return $this->_backtrace;
    }

    /**
     * Dispatch a report.
     *
     * @param object $log The report object to log.
     */
    public function log($type = null, $data = [])
    {
        if (!func_num_args()) {
            return $this->_log;
        }
        $this->report($type, $this->log()->add($type, $data));
    }

    /**
     * Send some data to reporters.
     *
     * @param string $type The message type.
     * @param mixed  $data The message data.
     */
    public function report($type, $data)
    {
        $suite = $this->suite();
        if ($suite->root()->focused() && !$this->focused()) {
            return;
        }
        $suite->report($type, $data);
    }

    /**
     * Return specs excecution results.
     *
     * @return array
     */
    public function summary()
    {
        return $this->_summary;
    }

    /**
     * Block processing.
     *
     * @param array $options Process options.
     */
    protected function _process($options = [])
    {
        $suite = $this->suite();
        if ($suite->root()->focused() && !$this->focused()) {
            return;
        }

        $this->_passed = true;

        if ($this->excluded()) {
            $this->log()->type('excluded');
            $this->summary()->log($this->log());
            $this->report('specEnd', $this->log());
            return;
        }
        $result = null;

        $suite::push($this);

        if ($suite::$PHP >= 7 && !defined('HHVM_VERSION')) {
            try {
                $this->_blockStart();
                try {
                    $result = $this->_execute();
                } catch (Throwable $exception) {
                    $this->_exception($exception);
                }
                $this->_blockEnd();
            } catch (Throwable $exception) {
                $this->_exception($exception, true);
                $this->_blockEnd(!$exception instanceof SkipException);
            }
        } else {
            try {
                $this->_blockStart();
                try {
                    $result = $this->_execute();
                } catch (Exception $exception) {
                    $this->_exception($exception);
                }
                $this->_blockEnd();
            } catch (Exception $exception) {
                $this->_exception($exception, true);
                $this->_blockEnd(!$exception instanceof SkipException);
            }
        }

        $suite::pop();

        return $this->_return = $result;
    }

    /**
     * Sets a lazy loaded data.
     *
     * @param  string  $name    The lazy loaded variable name.
     * @param  Closure $closure The lazily executed closure.
     * @return object
     */
    public function given($name, $closure)
    {
        $this->scope()->given($name, $closure);
        return $this;
    }

    /**
     * Skips specs(s) if the condition is `true`.
     *
     * @param  boolean       $condition
     *
     * @return self
     * @throws SkipException
     */
    public function skipIf($condition)
    {
        if (!$condition) {
            return;
        }
        $exception = new SkipException();
        throw $exception;
    }

    /**
     * Manage catched exception.
     *
     * @param Exception $exception  The catched exception.
     * @param boolean   $inEachHook Indicates if the exception occurs in a beforeEach/afterEach hook.
     */
    protected function _exception($exception, $inEachHook = false)
    {
        if ($exception instanceof SkipException) {
            !$inEachHook ? $this->log()->type('skipped') : $this->_skipChildren($exception);
            return;
        }
        $this->_passed = false;
        $this->log()->type('errored');
        $this->log()->exception($exception);
    }

    /**
     * Skip children specs(s).
     *
     * @param object  $exception The exception at the origin of the skip.
     * @param boolean $emit      Indicated if report events should be generated.
     */
    protected function _skipChildren($exception, $emit = false)
    {
        $log = $this->log();
        if ($this instanceof Group) {
            foreach ($this->children() as $child) {
                $child->_skipChildren($exception, true);
            }
        } elseif ($emit) {
            if (!$this->suite()->root()->focused() || $this->focused()) {
                $this->report('specStart', $this);
                $this->_passed = true;
                $this->log()->type('skipped');
                $this->summary()->log($this->log());
                $this->report('specEnd', $log);
            }
        } else {
            $this->_passed = true;
            $this->log()->type('skipped');
        }
    }

    /**
     * Apply focus up to the root.
     */
    protected function _emitFocus()
    {
        $this->summary()->add('focused', $this);
        $instances = $this->parents(true);

        foreach ($instances as $instance) {
            $instance->type('focus');
        }
    }

    /**
     * Bind the closure to the block's scope.
     *
     * @param  Closure $closure The closure to run
     *
     * @return Closure
     */
    protected function _bindScope($closure)
    {
        if (!is_callable($closure)) {
            return;
        }
        return @$closure->bindTo($this->_scope);
    }

    /**
     * Block execution helper.
     */
    abstract protected function _execute();

    /**
     * Start block execution helper.
     */
    abstract protected function _blockStart();

    /**
     * End block execution helper.
     */
    abstract protected function _blockEnd($runAfterAll = true);
}
