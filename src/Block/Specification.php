<?php
namespace Kahlan\Block;

use Closure;
use Exception;
use Kahlan\Expectation;
use Kahlan\Log;
use Kahlan\Scope\Specification as Scope;
use Kahlan\Suite;

class Specification extends \Kahlan\Block
{
    /**
     * List of expectations.
     * @var Expectation[]
     */
    protected $_expectations = [];

    /**
     * Constructor.
     *
     * @param array $config The Suite config array. Options are:
     *                      -`'closure'` _Closure_ : the closure of the test.
     *                      -`'message'` _string_  : the spec message.
     *                      -`'scope'`   _string_  : supported scope are `'normal'` & `'focus'`.
     */
    public function __construct($config = [])
    {
        $defaults = [
            'message' => 'passes'
        ];
        $config += $defaults;
        $config['message'] = 'it ' . $config['message'];

        parent::__construct($config);

        $this->_scope = new Scope(['block' => $this]);
        $this->_closure = $this->_bindScope($this->_closure);
    }

    /**
     * Reset the specification.
     */
    public function reset(): self
    {
        $this->_passed = null;
        $this->_expectations = [];
        $this->_log = new Log([
            'block' => $this,
            'backtrace' => $this->_backtrace
        ]);
        return $this;
    }

    /**
     * The assert statement.
     *
     * @param array $config The config array. Options are:
     *                       -`'actual'`  _mixed_   : the actual value.
     *                       -`'timeout'` _integer_ : the timeout value.
     *                       Or:
     *                       -`'handler'` _Closure_ : a delegated handler to execute.
     *                       -`'type'`    _string_  : delegated handler supported exception type.
     *
     * @return Expectation
     */
    public function assert(array $config = []): Expectation
    {
        return $this->_expectations[] = new Expectation($config);
    }

    /**
     * The expect statement (assert shortcut).
     *
     * @param mixed $actual The expression to check
     * @param int $timeout
     *
     * @return Expectation
     */
    public function expect($actual, int $timeout = 0): Expectation
    {
        return $this->_expectations[] = new Expectation(compact('actual', 'timeout'));
    }

    /**
     * The waitsFor statement.
     *
     * @param mixed $actual The expression to check
     * @param int $timeout
     *
     * @return \Kahlan\Expectation
     */
    public function waitsFor($actual, int $timeout = 0): Expectation
    {
        $timeout = $timeout ?: $this->timeout();
        $closure = $actual instanceof Closure ? $actual : function () use ($actual) {
            return $actual;
        };
        $spec = new static(['closure' => $closure]);

        return $this->expect($spec, $timeout);
    }

    /**
     * Spec execution helper.
     * @return mixed
     */
    protected function _execute()
    {
        $result = null;
        $spec = function () {
            $this->_expectations = [];
            $closure = $this->_closure;
            $result = $this->_suite->runBlock($this, $closure, 'specification');
            foreach ($this->_expectations as $expectation) {
                $this->_passed = $expectation->process() && $this->_passed;
            }
            return $result;
        };

        return $spec();
    }

    /**
     * Start spec execution helper.
     */
    protected function _blockStart(): void
    {
        $this->report('specStart', $this);
        if ($this->_parent) {
            $this->_parent->runCallbacks('beforeEach');
        }
    }

    /**
     * End spec execution helper.
     */
    protected function _blockEnd(bool $runAfterEach = true): void
    {
        $type = $this->log()->type();
        foreach ($this->_expectations as $expectation) {
            if (!($logs = $expectation->logs()) && $type !== 'errored') {
                $this->log()->type('pending');
            }
            foreach ($logs as $log) {
                $this->log($log['type'], $log);
            }
        }

        if ($type === 'passed' && empty($this->_expectations)) {
            $this->log()->type('pending');
        }
        $type = $this->log()->type();

        if ($type === 'failed' || $type === 'errored') {
            $this->_passed = false;
            $this->suite()->failure();
        }

        if ($this->_parent && $runAfterEach) {
            try {
                $this->_parent->runCallbacks('afterEach');
            } catch (Exception $exception) {
                $this->_exception($exception, true);
            }
        }

        $this->summary()->log($this->log());

        $this->report('specEnd', $this->log());

        Suite::current()->scope()->clear();
        $this->suite()->autoclear();
    }

    /**
     * Returns execution log.
     *
     * @return array
     */
    public function logs(): array
    {
        $logs = [];
        foreach ($this->_expectations as $expectation) {
            foreach ($expectation->logs() as $log) {
                $logs[] = $log;
            }
        }
        return $logs;
    }
}
