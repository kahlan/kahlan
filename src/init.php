<?php
use kahlan\Suite;
use kahlan\Specification;

if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}
error_reporting(E_ALL);

if (!defined('KAHLAN_DISABLE_FUNCTIONS') || !KAHLAN_DISABLE_FUNCTIONS) {

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

    function expect($actual) {
        return Specification::current()->expect($actual);
    }

    function waitsFor($actual, $timeout = null) {
        return Specification::current()->waitsFor($actual, $timeout);
    }

    function skipIf($condition) {
        $current = Specification::current() ?: Suite::current();
        return $current->skipIf($condition);
    }
}
