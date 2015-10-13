<?php
namespace kahlan;

use Closure;
use Exception;

class Specification extends Scope
{
    /**
     * Stores the success value.
     *
     * @var boolean
     */
    protected $_passed = true;

    /**
     * List of expectations.
     */
    protected $_expectations = [];

    /**
     * Constructor.
     *
     * @param array $config The Suite config array. Options are:
     *                       -`'closure'` _Closure_ : the closure of the test.
     *                       -`'scope'`   _string_  : supported scope are `'normal'` & `'focus'`.
     *                       -`'matcher'` _object_  : the matcher instance.
     */
    public function __construct($config = [])
    {
        $defaults = [
            'closure' => null,
            'message' => 'passes',
            'scope'   => 'normal'
        ];
        $config += $defaults;
        $config['message'] = 'it ' . $config['message'];
        parent::__construct($config);



        extract($config);

        $this->_closure = $this->_bind($closure, 'it');
        if ($scope === 'focus') {
            $this->_emitFocus();
        }
    }

    /**
     * The expect statement.
     *
     * @param mixed $actual The expression to check
     */
    public function expect($actual, $timeout = -1)
    {
        $expectation = $this->_classes['expectation'];
        return $this->_expectations[] = new $expectation(compact('actual', 'timeout'));
    }

    /**
     * The waitsFor statement.
     *
     * @param mixed $actual The expression to check
     */
    public function waitsFor($actual, $timeout = 0)
    {
        $timeout = $timeout ?: $this->timeout();
        $actual = $actual instanceof Closure ? $actual : function() {return $actual;};
        $spec = new static(['closure' => $actual]);
        return $this->expect($spec, $timeout);
    }

    /**
     * Processes a child specs.
     *
     * @see kahlan\Suite::process()
     * @param object A child spec.
     */
    public function process()
    {
        if ($this->_root->focused() && !$this->focused()) {
            return;
        }

        $result = null;

        try {
            $this->_specStart();
            try {
                $result = $this->run();
            } catch (Exception $exception) {
                $this->_exception($exception);
            } finally {
                foreach ($this->logs() as $log) {
                    $this->report()->add($log['type'], $log);
                }
            }
            $this->_specEnd();
        } catch (Exception $exception) {
            $this->_exception($exception, true);
            try {
                $this->_specEnd();
            } catch (Exception $exception) {}
        }

        return $result;
    }

    /**
     * Spec start helper.
     */
    protected function _specStart()
    {
        $this->emitReport('specStart', $this->report());
        if ($this->_parent) {
            $this->_parent->runCallbacks('beforeEach');
        }
    }

    /**
     * Spec end helper.
     */
    protected function _specEnd()
    {
        if (!$this->_parent) {
            $this->emitReport('specEnd', $this->report());
            return;
        }
        $this->_parent->runCallbacks('afterEach');
        $this->emitReport('specEnd', $this->report());
        $this->_parent->autoclear();
    }

    /**
     * Processes the spec.
     */
    public function run()
    {
        if ($this->_locked) {
            throw new Exception('Method not allowed in this context.');
        }

        $this->_locked = true;
        static::$_instances[] = $this;

        $result = null;
        $closure = $this->_closure;
        $this->_expectations = [];

        try {
            $this->_expectations = [];
            $result = $closure($this);
            foreach ($this->_expectations as $expectation) {
                if (!$expectation->runned()) {
                    $expectation->run();
                }
                $this->_passed = $this->_passed && $expectation->passed();
            }
        } catch (Exception $e) {
            $this->_passed = false;
            throw $e;
        } finally {
            array_pop(static::$_instances);
            $this->_locked = false;
        }

        return $result;
    }

    /**
     * Checks if all test passed.
     *
     * @return boolean Returns `true` if no error occurred, `false` otherwise.
     */
    public function passed()
    {
        return $this->_passed;
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
