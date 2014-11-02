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
     * Boolean lock which avoid `process()` to be called in tests
     *
     * @see khakan\Spec::process()
     */
    protected $_locked = false;

    /**
     * Constructor.
     *
     * @param array $options The Suite config array. Options are:
     *              -`'message'` : the description message.
     *              -`'closure'` : the closure of the test.
     *              -`'parent'` : the parent suite instance.
     */
    public function __construct($options = [])
    {
        $defaults = [
            'message' => '',
            'closure' => null,
            'parent' => null,
            'root' => null,
            'scope' => 'normal',
            'matcher' => null
        ];
        $options += $defaults;
        extract($options);

        $closure = $this->_bind($closure, 'it');
        $this->_message = 'it ' . $message;
        $this->_closure = $closure;
        $this->_parent = $parent;
        $this->_root = $root;
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
            $this->report('before');
            $closure($this);
            $this->_matcher->resolve();
            $this->report('after');
        } catch (Exception $exception) {
            try {
                $this->report('after');
            } catch (Exception $exception) {}
            $this->_exception($exception);
        }

        array_pop(static::$_instances);
        $this->_locked = false;
    }

}
