<?php
use box\Box;
use kahlan\Suite;
use kahlan\Spec;
use kahlan\Matcher;

global $gdic;

define('DS', DIRECTORY_SEPARATOR);
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

    function describe($message, $closure, $scope = 'normal') {
        if (!Suite::current()) {
            global $gdic;
            $suite = $gdic['kahlan']->get('suite.global');
            return $suite->describe($message, $closure, $scope);
        }
        return Suite::current()->describe($message, $closure, $scope);
    }

    function context($message, $closure, $scope = 'normal') {
        return Suite::current()->context($message, $closure, $scope);
    }

    function it($message, $closure = null, $scope = 'normal') {
        return Suite::current()->it($message, $closure, $scope);
    }

    function xdescribe($message, $closure) {
        return describe($message, $closure, 'exclusive');
    }

    function xcontext($message, $closure) {
        return context($message, $closure, 'exclusive');
    }

    function xit($message, $closure = null) {
        return it($message, $closure, 'exclusive');
    }

    function expect($actual) {
        return Spec::current()->expect($actual);
    }

    function skipIf($condition) {
        $current = Spec::current() ?: Suite::current();
        return $current->skipIf($condition);
    }
}

Matcher::register('toBe', 'kahlan\matcher\ToBe');
Matcher::register('toBeA', 'kahlan\matcher\ToBeA');
Matcher::register('toBeAn', 'kahlan\matcher\ToBeA');
Matcher::register('toBeAnInstanceOf', 'kahlan\matcher\ToBeAnInstanceOf');
Matcher::register('toBeCloseTo', 'kahlan\matcher\ToBeCloseTo');
Matcher::register('toBeEmpty', 'kahlan\matcher\ToBeFalsy');
Matcher::register('toBeFalsy', 'kahlan\matcher\ToBeFalsy');
Matcher::register('toBeGreaterThan', 'kahlan\matcher\ToBeGreaterThan');
Matcher::register('toBeLessThan', 'kahlan\matcher\ToBeLessThan');
Matcher::register('toBeNull', 'kahlan\matcher\ToBeNull');
Matcher::register('toBeTruthy', 'kahlan\matcher\ToBeTruthy');
Matcher::register('toContain', 'kahlan\matcher\ToContain');
Matcher::register('toEcho', 'kahlan\matcher\ToEcho');
Matcher::register('toEqual', 'kahlan\matcher\ToEqual');
Matcher::register('toHaveLength', 'kahlan\matcher\ToHaveLength');
Matcher::register('toMatch', 'kahlan\matcher\ToMatch');
Matcher::register('toReceive', 'kahlan\matcher\ToReceive');
Matcher::register('toReceiveNext', 'kahlan\matcher\ToReceiveNext');
Matcher::register('toThrow', 'kahlan\matcher\ToThrow');

$dic = $gdic['kahlan'] = new Box();

$dic->factory('matcher', function() {
    return new Matcher();
});

$dic->factory('suite', function() {
    return new Suite(['matcher' => $this->get('matcher')]);
});

$dic->service('suite.global', function() {
    return $this->get('suite');
});

?>