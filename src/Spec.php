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
     *                       -`'scope'`   _string_  : supported scope are `'normal'` & `'exclusive'`.
     *                       -`'matcher'` _object_  : the matcher instance.
     */
    public function __construct($options = [])
    {
        $defaults = [
            'closure' => null,
            'scope' => 'normal',
            'matcher' => null
        ];
        $options += $defaults;
        $options['message'] = 'it ' . $options['message'];
        parent::__construct($options);

        extract($options);

        $closure = $this->_bind($closure, 'it');
        $this->_closure = $closure;
        $this->_emitExclusive($scope);
        $this->_matcher = $options['matcher'];
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
     * Process the spec.
     */
    public function process()
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
