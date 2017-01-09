<?php
namespace Kahlan\Block;

use Closure;
use Throwable;
use Exception;
use Kahlan\Expectation;
use Kahlan\Suite;
use Kahlan\Scope\Specification as Scope;

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
     * The expect statement.
     *
     * @param  Expectation   $actual The expression to check
     *
     * @return Expectation[]
     */
    public function expect($actual, $timeout = -1)
    {
        return $this->_expectations[] = new Expectation(compact('actual', 'timeout'));
    }

    /**
     * The waitsFor statement.
     *
     * @param  Expectation $actual The expression to check
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
     * Processes the spec.
     */
    protected function _execute()
    {
        $result = null;
        $spec = function () {
            $this->_expectations = [];
            $closure = $this->_closure;
            $result = $closure($this);
            foreach ($this->_expectations as $expectation) {
                $this->_passed = $expectation->process() && $this->_passed;
            }
            return $result;
        };

        $suite = $this->suite();
        return $spec();
    }

    /**
     * Spec start helper.
     */
    protected function _blockStart()
    {
        $this->report('specStart', $this);
        if ($this->_parent) {
            $this->_parent->runCallbacks('beforeEach');
        }
    }

    /**
     * Spec end helper.
     */
    protected function _blockEnd($runAfterEach = true)
    {
        foreach ($this->_expectations as $expectation) {
            foreach ($expectation->logs() as $log) {
                $this->log($log['type'], $log);
            }
        }

        if ($this->log()->type() === 'passed' && !count($this->_expectations)) {
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
