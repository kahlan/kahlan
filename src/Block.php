<?php declare(strict_types=1);

namespace Kahlan;

use Closure;
use Exception;
use Throwable;
use Kahlan\Block\Group;
use Kahlan\Analysis\Debugger;

abstract class Block
{

    /**
     * The block type.
     *
     * @var string can be 'normal'|'focus'|'exclude'
     */
    protected $_type = '';

    /**
     * @var \Kahlan\Suite
     */
    protected $_suite = null;

    /**
     * @var Scope|null
     */
    protected $_parent = null;

    /**
     * @var string
     */
    protected $_message = '';

    /**
     * @var integer
     */
    protected $_timeout = 0;

    /**
     * @var Scope
     */
    protected $_scope = null;

    /**
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
     * @var boolean|null
     */
    protected $_passed = null;

    /**
     * The scope backtrace.
     *
     * @var array
     */
    protected $_backtrace = [];

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
     *                       -`'suite'`   _object_ : the \Kahlan\Suite instance.
     *                       -`'parent'`  _object_ : the parent block - instance of \Kahlan\Suite or null.
     *                       -`'type'`    _string_ : supported type are `'normal'`, `'focus'` and `'exclude'`.
     *                       -`'message'` _string_ : the description message.
     *                       -`'closure'` _Closure_: the closure of the test.
     *                       -`'log'`     _object_ : the \Kahlan\Log instance.
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
        if (!$this->_suite instanceof Suite) {
            throw new \InvalidArgumentException('Expected `suite` to be an instance of ' . Suite::class);
        }
        $this->_parent  = $config['parent'];
        if ($this->_parent !== null && ($this->_parent instanceof Suite)) {
            throw new \InvalidArgumentException('Expected `parent` to be an instance of ' . Suite::class . 'or null');
        }
        $this->_type    = $config['type'];
        if (!in_array($this->_type, ['normal', 'focus', 'exclude'])) {
            throw new \InvalidArgumentException('Expected `type` to be `normal`, `focus` or `exclude`');
        }
        $this->_message = $config['message'];
        if (!is_string($this->_message)) {
            throw new \InvalidArgumentException('Expected `message` to be a string');
        }
        $this->_closure = $config['closure'] ?: function () {};
        if (!($this->_closure instanceof Closure)) {
            throw new \InvalidArgumentException('Expected `closure` to be an instance of ' . Closure::class);
        }
        $this->_timeout = $config['timeout'];
        if (!is_int($this->_timeout)) {
            throw new \InvalidArgumentException('Expected `timeout` to be int');
        }

        $suite = $this->suite();
        $this->_backtrace = Debugger::focus($suite->backtraceFocus(), Debugger::backtrace(), 1);

        $this->_log = $config['log'] ?: new Log([
            'block' => $this,
            'backtrace' => $this->_backtrace
        ]);
        if (!($this->_log instanceof Log)) {
            throw new \InvalidArgumentException('Expected `log` to be an instance of ' . Log::class);
        }

        $this->_summary = $suite->summary();

        if ($this->_type === 'focus') {
            $this->_emitFocus();
        }
    }

    public function suite(): Suite
    {
        return $this->_suite;
    }

    public function parent()
    {
        return $this->_parent;
    }

    public function message(): string
    {
        return $this->_message;
    }

    public function scope(): Scope
    {
        return $this->_scope;
    }

    public function closure(): Closure
    {
        return $this->_closure;
    }

    /**
     * @return boolean|null bool if test passed or not and `null` if not run
     */
    public function process(&$return = null): ?bool
    {
        if ($this->_passed === null) {
            $this->_process();
        }
        $return = $this->_return;
        return $this->_passed;
    }

    /**
     * @return boolean|null bool if test passed or not and `null` if not run
     */
    public function passed(): ?bool
    {
        return $this->_passed;
    }

    /**
     * Set/get the block type.
     */
    public function type(string $type = ''): ?string
    {
        if (!func_num_args()) {
            return $this->_type;
        }
        $this->_type = $type;
        return $this->_type;
    }

    public function excluded(): bool
    {
        return $this->_type === 'exclude';
    }

    public function focused(): bool
    {
        return $this->_type === 'focus';
    }

    /**
     * Return all parent block instances.
     *
     * @param  boolean $current If `true` include `$this` to the list.
     */
    public function parents(bool $current = false): array
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
     */
    public function messages(): array
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
     */
    public function timeout(int $timeout = null): int
    {
        if (func_num_args()) {
            $this->_timeout = $timeout;
        }
        return $this->_timeout;
    }

    public function backtrace(): array
    {
        return $this->_backtrace;
    }

    public function log(string $type = null, array $data = []): Log
    {
        if (!func_num_args()) {
            return $this->_log;
        }
        $this->report($type, $this->log()->add($type, $data));
        return $this->_log;
    }

    /**
     * Send some data to reporters.
     * @TODO what data?
     */
    public function report(string $type, object $data)
    {
        $suite = $this->suite();
        if ($suite->root()->focused() && !$this->focused()) {
            return;
        }
        $suite->report($type, $data);
    }

    public function summary(): Summary
    {
        return $this->_summary;
    }

    protected function _process(): void
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

        $suite::pop();

        $this->_return = $result;
    }

    /**
     * Sets a lazy loaded data.
     *
     * @param  string  $name    The lazy loaded variable name.
     * @param  Closure $closure The lazily executed closure.
     */
    public function given(string $name, Closure $closure): self
    {
        $this->scope()->given($name, $closure);
        return $this;
    }

    /**
     * @param  bool $condition skip specs if `true`
     *
     * @throws SkipException
     */
    public function skipIf(bool $condition)
    {
        if ($condition) {
            throw new SkipException();
        }
    }

    /**
     * Manage catched exception.
     *
     * @param Throwable $exception  The catched exception.
     * @param boolean   $inEachHook Indicates if the exception occurs in a beforeEach/afterEach hook.
     */
    protected function _exception(Throwable $exception, bool $inEachHook = false): void
    {
        if ($exception instanceof SkipException) {
            if (!$inEachHook) {
                $this->log()->type('skipped');
            } else {
                $this->_skipChildren($exception);
            }
            return;
        }
        $this->_passed = false;
        $this->log()->type('errored');
        $this->log()->exception($exception);
    }

    /**
     * Skip children specs(s).
     *
     * @param Throwable $exception The exception at the origin of the skip.
     * @param boolean   $emit      Indicated if report events should be generated.
     */
    protected function _skipChildren(Throwable $exception, bool $emit = false): void
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
    protected function _emitFocus(): void
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
    protected function _bindScope(Closure $closure): Closure
    {
        return $closure->bindTo($this->_scope);
    }

    /**
     * Block execution helper.
     */
    abstract protected function _execute();

    /**
     * Start block execution helper.
     */
    abstract protected function _blockStart(): void;

    /**
     * End block execution helper.
     */
    abstract protected function _blockEnd(bool $runAfterAll = true): void;
}
