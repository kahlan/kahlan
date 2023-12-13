<?php
namespace Kahlan;

use Kahlan\Box\Box;
use Kahlan\Box\BoxException;

function beforeAll($closure)
{
    return Suite::current()->beforeAll($closure);
}

function afterAll($closure)
{
    return Suite::current()->afterAll($closure);
}

function beforeEach($closure)
{
    return Suite::current()->beforeEach($closure);
}

function afterEach($closure)
{
    return Suite::current()->afterEach($closure);
}

function describe($message, $closure, $timeout = null, $type = 'normal')
{
    if (!Suite::current()) {
        $suite = box('kahlan')->get('suite.global');
        return $suite->root()->describe($message, $closure, $timeout, $type);
    }
    return Suite::current()->describe($message, $closure, $timeout, $type);
}

function context($message, $closure, $timeout = null, $type = 'normal')
{
    return Suite::current()->context($message, $closure, $timeout, $type);
}

function given($name, $value)
{
    return Suite::current()->given($name, $value);
}

function it($message, $closure = null, $timeout = null, $type = 'normal')
{
    return Suite::current()->it($message, $closure, $timeout, $type);
}

/**
 * @param iterable<array-key, array> $cases
 *
 * @return CasesBuilder
 */
function withEach($cases, $timeout = null, $type = 'normal')
{
    return Suite::current()->withEach($cases, $timeout, $type);
}

function fdescribe($message, $closure, $timeout = null)
{
    return describe($message, $closure, $timeout, 'focus');
}

function fcontext($message, $closure, $timeout = null)
{
    return context($message, $closure, $timeout, 'focus');
}

function fit($message, $closure = null, $timeout = null)
{
    return it($message, $closure, $timeout, 'focus');
}

/**
 * @param iterable<array-key, array> $cases
 *
 * @return CasesBuilder
 */
function fwithEach($cases, $timeout = null)
{
    return withEach($cases, $timeout, 'focus');
}

function xdescribe($message, $closure, $timeout = null)
{
    return describe($message, $closure, $timeout, 'exclude');
}

function xcontext($message, $closure, $timeout = null)
{
    return context($message, $closure, $timeout, 'exclude');
}

function xit($message, $closure = null, $timeout = null)
{
    return it($message, $closure, $timeout, 'exclude');
}

/**
 * @param iterable<array-key, array> $cases
 *
 * @return CasesBuilder
 */
function xwithEach($cases, $timeout = null)
{
    return withEach($cases, $timeout, 'exclude');
}

function waitsFor($actual, $timeout = null)
{
    return Suite::current()->waitsFor($actual, $timeout);
}

function skipIf($condition)
{
    $current = Suite::current();
    $current->skipIf($condition);
}

/**
 * @param $actual
 *
 * @return Expectation
 */
function expect($actual)
{
    return Suite::current()->expect($actual);
}

/**
 * @param $actual
 *
 * @return Allow
 */
function allow($actual)
{
    return new Allow($actual);
}

function box($name = '', $box = null)
{
    static $boxes = [];

    if (func_num_args() === 1) {
        if ($name === false) {
            $boxes = [];
            return;
        }
        if (is_object($name)) {
            return $boxes[''] = $name;
        }
        if (isset($boxes[$name])) {
            return $boxes[$name];
        }
        throw new BoxException("Unexisting box `'{$name}'`.");
    }
    if (func_num_args() === 2) {
        if ($box === false) {
            unset($boxes[$name]);
            return;
        }
        return $boxes[$name] = $box;
    }
    if (!isset($boxes[''])) {
        $boxes[''] = new Box();
    }
    return $boxes[''];
}
