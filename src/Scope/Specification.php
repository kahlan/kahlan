<?php
namespace Kahlan\Scope;

use Closure;
use Throwable;
use Exception;
use Kahlan\Expectation;

class Specification extends \Kahlan\Scope
{
    /**
     * The expect statement.
     *
     * @param  mixed $actual The expression to check
     *
     * @return Expectation;
     */
    public function expect($actual, $timeout = -1)
    {
        return $this->_block->expect($actual, $timeout);
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
        return $this->_block->waitsFor($actual, $timeout);
    }
}
