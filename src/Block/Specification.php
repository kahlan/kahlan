<?php
namespace Kahlan\Block;

use Closure;
use Exception;
use Kahlan\Expectation;
use Kahlan\ExternalExpectation;
use Kahlan\Scope\Specification as Scope;
use Kahlan\Suite;
use Throwable;

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
    public function assert($config = [])
    {
        return $this->_expectations[] = new Expectation($config);
    }

    /**
     * The expect statement (assert shortcut).
     *
     * @param  mixed       $actual The expression to check
     *
     * @return Expectation
     */
    public function expect($actual, $timeout = -1)
    {
        return $this->_expectations[] = new Expectation(compact('actual', 'timeout'));
    }

    /**
     * The waitsFor statement.
     *
     * @param  mixed $actual The expression to check
     *
     * @return mixed
     */
    public function waitsFor($actual, $timeout = 0)
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

        $suite = $this->suite();
        return $spec();
    }

    /**
     * Start spec execution helper.
     */
    protected function _blockStart()
    {
        $this->report('specStart', $this);
        if ($this->_parent) {
            $this->_parent->runCallbacks('beforeEach');
        }
    }

    /**
     * End spec execution helper.
     */
    protected function _blockEnd($runAfterEach = true)
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
    public function logs()
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
