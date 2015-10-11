<?php
namespace kahlan;

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
     * The matcher instance.
     *
     * @var object
     */
    protected $_matcher = null;

    /**
     * Constructor.
     *
     * @param array $options The Suite config array. Options are:
     *                       -`'closure'` _Closure_ : the closure of the test.
     *                       -`'scope'`   _string_  : supported scope are `'normal'` & `'focus'`.
     *                       -`'matcher'` _object_  : the matcher instance.
     */
    public function __construct($options = [])
    {
        $defaults = [
            'closure' => null,
            'scope'   => 'normal'
        ];
        $options += $defaults;
        $options['message'] = 'it ' . $options['message'];
        parent::__construct($options);

        $matcher = $this->_classes['matcher'];
        $this->_matcher = new $matcher();

        extract($options);

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
    public function expect($actual)
    {
        return $this->_matcher->expect($actual);
    }

    /**
     * The wait statement.
     *
     * @param mixed $actual The expression to check
     */
    public function wait($actual, $timeout = 0)
    {
        $timeout = $timeout ?: $this->timeout();
        return $this->_matcher->expect($actual, $timeout);
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
                foreach ($this->_matcher->logs() as $log) {
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

        try {
            $result = $closure($this);
            $this->_matcher->resolve();
            $this->_passed = $this->_matcher->passed();
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
}
