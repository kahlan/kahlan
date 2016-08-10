<?php
use Kahlan\Expectation;
use Kahlan\Suite;
use Kahlan\Specification;
use Kahlan\Box\BoxException;
use Kahlan\Box\Box;

if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}
error_reporting(E_ALL);

$kahlanFuctions = true;

if (getenv('KAHLAN_DISABLE_FUNCTIONS') || (defined('KAHLAN_DISABLE_FUNCTIONS') && KAHLAN_DISABLE_FUNCTIONS)) {
    $kahlanFuctions = false;
}

if (defined('KAHLAN_FUNCTIONS_EXIST') && KAHLAN_FUNCTIONS_EXIST) {
    $kahlanFuctions = false;
}

if ($kahlanFuctions &&
    !function_exists('before') &&
    !function_exists('after') &&
    !function_exists('beforeEach') &&
    !function_exists('afterEach') &&
    !function_exists('describe') &&
    !function_exists('context') &&
    !function_exists('given') &&
    !function_exists('it') &&
    !function_exists('fdescribe') &&
    !function_exists('fcontext') &&
    !function_exists('fit') &&
    !function_exists('xdescribe') &&
    !function_exists('xcontext') &&
    !function_exists('waitsFor') &&
    !function_exists('skipIf') &&
    !function_exists('expect')) {

    define('KAHLAN_FUNCTIONS_EXIST', true);

    function before($closure) {
        return Suite::current()->before($closure);
    }

    function after($closure) {
        return Suite::current()->after($closure);
    }

    function beforeEach($closure) {
        return Suite::current()->beforeEach($closure);
    }

    function afterEach($closure) {
        return Suite::current()->afterEach($closure);
    }

    function describe($message, $closure, $timeout = null, $scope = 'normal') {
        if (!Suite::current()) {
            $suite = box('kahlan')->get('suite.global');
            return $suite->describe($message, $closure, $timeout, $scope);
        }
        return Suite::current()->describe($message, $closure, $timeout, $scope);
    }

    function context($message, $closure, $timeout = null, $scope = 'normal') {
        return Suite::current()->context($message, $closure, $timeout, $scope);
    }

    function given($name, $value) {
        return Suite::current()->given($name, $value);
    }

    function it($message, $closure, $timeout = null, $scope = 'normal') {
        return Suite::current()->it($message, $closure, $timeout, $scope);
    }

    function fdescribe($message, $closure, $timeout = null) {
        return describe($message, $closure, $timeout, 'focus');
    }

    function fcontext($message, $closure, $timeout = null) {
        return context($message, $closure, $timeout, 'focus');
    }

    function fit($message, $closure = null, $timeout = null) {
        return it($message, $closure, $timeout, 'focus');
    }

    function xdescribe($message, $closure) {
    }

    function xcontext($message, $closure) {
    }

    function xit($message, $closure = null) {
    }

    function waitsFor($actual, $timeout = null) {
        return Specification::current()->waitsFor($actual, $timeout);
    }

    function skipIf($condition) {
        $current = Specification::current() ?: Suite::current();
        return $current->skipIf($condition);
    }

    /**
     * @param $actual
     *
     * @return Expectation
     */
    function expect($actual) {
        return Specification::current()->expect($actual);
    }
}

$boxFuctions = true;

if (getenv('BOX_DISABLE_FUNCTIONS') || (defined('BOX_DISABLE_FUNCTIONS') && BOX_DISABLE_FUNCTIONS)) {
    $boxFuctions = false;
}

if (defined('BOX_FUNCTIONS_EXIST') && BOX_FUNCTIONS_EXIST) {
    $boxFuctions = false;
}

if ($boxFuctions && !function_exists('box')) {
    define('BOX_FUNCTIONS_EXIST', true);

    function box($name = '', $box = null) {
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

}
