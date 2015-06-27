<?php
namespace kahlan;

use Exception;

class Spec extends Scope
{
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
            'scope'   => 'normal',
            'matcher' => null
        ];
        $options += $defaults;
        $options['message'] = 'it ' . $options['message'];
        parent::__construct($options);

        extract($options);

        $closure        = $this->_bind($closure, 'it');
        $this->_closure = $closure;
        $this->_matcher = $options['matcher'];

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
        return $this->_matcher->expect($actual, $this);
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

        try {
            $this->_specStart();
            try {
                $this->run();
            } catch (Exception $exception) {
                $this->_exception($exception);
            }
            $this->_specEnd();
        } catch (Exception $exception) {
            $this->_exception($exception, true);
            try {
                $this->_specEnd();
            } catch (Exception $exception) {}
        }
    }

    /**
     * Spec start helper.
     */
    protected function _specStart()
    {
        $this->emitReport('specStart', $this->report());
        $this->_parent->runCallbacks('beforeEach');
    }

    /**
     * Spec end helper.
     */
    protected function _specEnd()
    {
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

        $closure = $this->_closure;

        try {
            $closure($this);
            $this->_matcher->resolve();
        } catch (Exception $exception) {
            $this->_exception($exception);
        }

        array_pop(static::$_instances);
        $this->_locked = false;
    }

}
