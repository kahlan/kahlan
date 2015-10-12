<?php
namespace kahlan;

use Exception;
use kahlan\analysis\Debugger;

class Scope
{
    /**
     * Class dependencies.
     *
     * @var array
     */
    protected $_classes = [
        'expectation' => 'kahlan\Expectation'
    ];

    /**
     * Instances stack.
     *
     * @var array
     */
    protected static $_instances = [];

    /**
     * A regexp pattern used to removes useless traces to focus on the one
     * related to a spec file.
     *
     * @var string
     */
    protected $_backtraceFocus = null;

    /**
     * The scope backtrace.
     *
     * @var object
     */
    protected $_backtrace = null;

    /**
     * List of reserved keywords which can't be used as scope variable.
     *
     * @var array
     */
    protected static $_reserved = [
        '__construct' => true,
        '__call'      => true,
        '__get'       => true,
        '__set'       => true,
        'after'       => true,
        'afterEach'   => true,
        'before'      => true,
        'beforeEach'  => true,
        'context'     => true,
        'current'     => true,
        'describe'    => true,
        'dispatch'    => true,
        'emitReport'  => true,
        'focus'       => true,
        'focused'     => true,
        'expect'      => true,
        'failfast'    => true,
        'hash'        => true,
        'it'          => true,
        'logs'        => true,
        'matcher'     => true,
        'message'     => true,
        'messages'    => true,
        'passed'      => true,
        'process'     => true,
        'register'    => true,
        'registered'  => true,
        'report'      => true,
        'reset'       => true,
        'results'     => true,
        'run'         => true,
        'skipIf'      => true,
        'status'      => true,
        'timeout'     => true,
        'wait'        => true,
        'fdescribe'   => true,
        'fcontext'    => true,
        'fit'         => true,
        'xdescribe'   => true,
        'xcontext'    => true,
        'xit'         => true
    ];

    /**
     * The root instance.
     *
     * @var object
     */
    protected $_root = null;

    /**
     * The parent instance.
     *
     * @var object
     */
    protected $_parent = null;

    /**
     * The spec message.
     *
     * @var string
     */
    protected $_message = null;

    /**
     * The spec closure.
     *
     * @var Closure
     */
    protected $_closure = null;

    /**
     * The scope's data.
     *
     * @var array
     */
    protected $_data = [];

    /**
     * The report result of executed spec.
     *
     * @var object
     */
    protected $_report = null;

    /**
     * The results array.
     *
     * @var array
     */
    protected $_results = [
        'passed'     => [],
        'failed'     => [],
        'skipped'    => [],
        'exceptions' => [],
        'incomplete' => []
    ];

    /**
     * The matching beetween events name & result types.
     *
     * @var array
     */
    protected $_resultTypes = [
        'pass'       => 'passed',
        'fail'       => 'failed',
        'skip'       => 'skipped',
        'exception'  => 'exceptions',
        'incomplete' => 'incomplete'
    ];

    /**
     * Focused scope detected.
     *
     * @var array
     */
    protected $_focused = false;

    /**
     * Count the number of failure or exception.
     *
     * @see ::failfast()
     * @var integer
     */
    protected $_failure = 0;

    /**
     * The reporters container.
     *
     * @var object
     */
    protected $_reporters = null;

    /**
     * Boolean lock which avoid `process()` to be called in tests
     */
    protected $_locked = false;

    /**
     * The timeout value.
     *
     * @var integer
     */
    protected $_timeout = 0;

    /**
     * The Constructor.
     *
     * @param array $config The Suite config array. Options are:
     *                       -`'message'` _string_ : the description message.
     *                       -`'parent'`  _object_ : the parent scope.
     *                       -`'root'`    _object_ : the root scope.
     */
    public function __construct($config = [])
    {
        $defaults = [
            'message' => '',
            'parent'  => null,
            'root'    => null,
            'timeout' => 0,
            'classes' => []
        ];
        $config += $defaults;
        $this->_classes += $config['classes'];
        extract($config);

        $this->_message   = $message;
        $this->_parent    = $parent;
        $this->_root      = $parent ? $parent->_root : $this;
        $this->_report    = new Report(['scope' => $this]);
        $this->_timeout   = $timeout;
        $this->_backtrace = Debugger::focus($this->backtraceFocus(), Debugger::backtrace(), 1);
    }

    /**
     * Getter.
     *
     * @param  string $key The name of the variable.
     * @return mixed  The value of the variable.
     */
    public function &__get($key)
    {
        if (array_key_exists($key, $this->_data)) {
            return $this->_data[$key];
        }
        if ($this->_parent !== null) {
            return $this->_parent->__get($key);
        }
        if (in_array($key, static::$_reserved)) {
            if ($key == 'expect') {
                throw new Exception("You can't use expect() inside of describe()");
            }
        }
        throw new Exception("Undefined variable `{$key}`.");
    }

    /**
     * Setter.
     *
     * @param  string $key   The name of the variable.
     * @param  mixed  $value The value of the variable.
     * @return mixed  The value of the variable.
     */
    public function __set($key, $value)
    {
        if (isset(static::$_reserved[$key])) {
            throw new Exception("Sorry `{$key}` is a reserved keyword, it can't be used as a scope variable.");
        }
        return $this->_data[$key] = $value;
    }

    /**
     * Allow closures assigned to the scope property to be inkovable.
     *
     * @param  string $name   Name of the method being called.
     * @param  array  $params Enumerated array containing the passed parameters.
     * @return mixed
     * @throws Throw an Exception if the property doesn't exists / is not callable.
     */
    public function __call($name, $params)
    {
        $property = null;
        $property = $this->__get($name);

        if (is_callable($property)) {
            return call_user_func_array($property, $params);
        }
        throw new Exception("Uncallable variable `{$name}`.");
    }

    /**
     * Gets the parent instance.
     *
     * @return array
     */
    public function parent()
    {
        return $this->_parent;
    }

    /**
     * Gets the spec's message.
     *
     * @return array
     */
    public function message()
    {
        return $this->_message;
    }

    /**
     * Gets all messages upon the root.
     *
     * @return array
     */
    public function messages()
    {
        $messages = [];
        $instances = $this->_parents(true);
        foreach ($instances as $instance) {
            $messages[] = $instance->message();
        }
        return $messages;
    }

    /**
     * Skips specs(s) if the condition is `true`.
     *
     * @param boolean $condition
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
     * Skips childs specs(s).
     *
     * @param object  $exception The exception at the origin of the skip.
     * @param boolean $emit      Indicated if report events should be generated.
     */
    protected function _skipChilds($exception, $emit = false)
    {
        $report = $this->report();
        if ($this instanceof Suite) {
            foreach ($this->_childs as $child) {
                $child->_skipChilds($exception, true);
            }
        } elseif ($emit) {
            $this->emitReport('specStart', $report);
            $report->add('skip', ['exception' => $exception]);
            $this->emitReport('specEnd', $report);
        } else {
            $report->add('skip', ['exception' => $exception]);
        }
    }

    /**
     * Manages catched exception.
     *
     * @param Exception $exception  The catched exception.
     * @param boolean   $inEachHook Indicates if the exception occurs in a beforeEach/afterEach hook.
     */
    protected function _exception($exception, $inEachHook = false)
    {
        $data = compact('exception');
        switch(get_class($exception)) {
            case 'kahlan\SkipException':
                if ($inEachHook) {
                    $this->report()->add('skip', $data);
                } else {
                    $this->_skipChilds($exception);
                }
            break;
            case 'kahlan\IncompleteException':
                $this->report()->add('incomplete', $data);
            break;
            default:
                $this->report()->add('exception', $data);
            break;
        }
    }

    /**
     * Gets all parent instances.
     *
     * @param  boolean $current If `true` include `$this` to the list.
     * @return array.
     */
    protected function _parents($current = false)
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
     * Binds the closure to the current context.
     *
     * @param  Closure  $closure The variable to check
     * @param  string   $name Name of the parent type (TODO: to use somewhere).
     * @throws Throw an Exception if the passed parameter is not a closure
     */
    protected function _bind($closure, $name)
    {
        if (!is_callable($closure)) {
            throw new Exception("Error, invalid closure.");
        }
        return $closure->bindTo($this);
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
            return $this->_root->_backtraceFocus;
        }
        return $this->_root->_backtraceFocus = strtr(preg_quote($pattern, '~'), ['\*' => '.*', '\?' => '.']);
    }

    /**
     * Sets focused mode.
     *
     * @param  boolean The focus mode.
     * @return boolean
     */
    public function focus($state = true)
    {
        return $this->_focused = $state;
    }

    /**
     * Gets focused mode.
     *
     * @param  boolean|null For the setter behavior.
     * @return boolean
     */
    public function focused()
    {
        return $this->_focused;
    }

    /**
     * Applies focus up to the root.
     */
    protected function _emitFocus()
    {
        $this->_root->_focuses[] = Debugger::focus($this->backtraceFocus(), Debugger::backtrace());
        $instances = $this->_parents(true);

        foreach ($instances as $instance) {
            $instance->focus();
        }
    }

    /**
     * Gets specs excecution results.
     *
     * @return array
     */
    public function results()
    {
        return $this->_results;
    }

    /**
     * Notifies a failure occurs.
     */
    public function failure()
    {
        $this->_root->_failure++;
    }

    /**
     * Get the active scope instance.
     *
     * @return object The object instance or `null` if there's no active instance.
     */
    public static function current()
    {
        return end(static::$_instances);
    }

    /**
     * Dispatches a report up to the root scope.
     * It only logs expectations report.
     *
     * @param object $report The report object to log.
     */
    public function dispatch($report)
    {
        $resultType = $this->_resultTypes[$report->type()];
        $this->_root->_results[$resultType][] = $report;

        $this->emitReport($report->type(), $report);
    }

    /**
     * Emit a report even up to reporters.
     *
     * @param string $type The name of the report.
     * @param array  $data The data to report.
     */
    public function emitReport($type, $data = null)
    {
        if (!$this->_root->_reporters) {
            return;
        }
        $this->_root->_reporters->process($type, $data);
    }

    /**
     * Gets the report instance.
     *
     * @return object The report instance.
     */
    public function report()
    {
        return $this->_report;
    }

    /**
     * Gets the reporters container.
     *
     * @return object
     */
    public function reporters()
    {
        return $this->_root->_reporters;
    }

    /**
     * Gets/sets the timeout.
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

}
