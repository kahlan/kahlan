<?php
namespace Kahlan\Scope;

use Closure;

class Group extends \Kahlan\Scope
{
    /**
     * Adds a group/class related spec.
     *
     * @param  string  $message Description message.
     * @param  Closure $closure A test case closure.
     *
     * @return Group
     */
    public function describe($message, $closure, $timeout = null, $type = 'normal')
    {
        return $this->_block->describe($message, $closure, $timeout, $type);
    }

    /**
     * Adds a context related spec.
     *
     * @param  string  $message Description message.
     * @param  Closure $closure A test case closure.
     * @param  null    $timeout
     * @param  string  $type
     *
     * @return Group
     */
    public function context($message, $closure, $timeout = null, $type = 'normal')
    {
        return $this->_block->context($message, $closure, $timeout, $type);
    }

    /**
     * Adds a spec.
     *
     * @param  string|Closure $message Description message or a test closure.
     * @param  Closure        $closure A test case closure.
     * @param  string         $type   The type.
     *
     * @return Specification
     */
    public function it($message, $closure = null, $timeout = null, $type = 'normal')
    {
        return $this->_block->it($message, $closure, $timeout, $type);
    }

    /**
     * Comments out a group/class related spec.
     *
     * @param  string  $message Description message.
     * @param  Closure $closure A test case closure.
     *
     * @return
     */
    public function xdescribe($message, $closure, $timeout = null)
    {
        return $this->_block->describe($message, $closure, $timeout, 'exclude');
    }

    /**
     * Comments out a context related spec.
     *
     * @param  string  $message Description message.
     * @param  Closure $closure A test case closure.
     *
     * @return
     */
    public function xcontext($message, $closure, $timeout = null)
    {
        return $this->_block->context($message, $closure, $timeout, 'exclude');
    }

    /**
     * Comments out a spec.
     *
     * @param  string|Closure $message Description message or a test closure.
     * @param  Closure|null   $closure A test case closure or `null`.
     *
     * @return
     */
    public function xit($message, $closure = null, $timeout = null)
    {
        return $this->_block->it($message, $closure, $timeout, 'exclude');
    }

    /**
     * Adds an focused group/class related spec.
     *
     * @param  string  $message Description message.
     * @param  Closure $closure A test case closure.
     *
     * @return Group
     */
    public function fdescribe($message, $closure, $timeout = null)
    {
        return $this->_block->describe($message, $closure, $timeout, 'focus');
    }

    /**
     * Adds an focused context related spec.
     *
     * @param  string  $message Description message.
     * @param  Closure $closure A test case closure.
     *
     * @return Group
     */
    public function fcontext($message, $closure, $timeout = null)
    {
        return $this->_block->context($message, $closure, $timeout, 'focus');
    }

    /**
     * Adds an focused spec.
     *
     * @param  string|Closure $message Description message or a test closure.
     * @param  Closure|null   $closure A test case closure or `null`.
     *
     * @return Specification
     */
    public function fit($message, $closure = null, $timeout = null)
    {
        return $this->_block->it($message, $closure, $timeout, 'focus');
    }

    /**
     * Executed before tests.
     *
     * @param  Closure $closure A closure
     *
     * @return self
     */
    public function beforeAll($closure)
    {
        $this->_block->beforeAll($closure);
        return $this;
    }

    /**
     * Executed after tests.
     *
     * @param  Closure $closure A closure
     *
     * @return self
     */
    public function afterAll($closure)
    {
        $this->_block->afterAll($closure);
        return $this;
    }

    /**
     * Executed before each tests.
     *
     * @param  Closure $closure A closure
     *
     * @return self
     */
    public function beforeEach($closure)
    {
        $this->_block->beforeEach($closure);
        return $this;
    }

    /**
     * Executed after each tests.
     *
     * @param  Closure $closure A closure
     *
     * @return self
     */
    public function afterEach($closure)
    {
        $this->_block->afterEach($closure);
        return $this;
    }
}
