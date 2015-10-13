<?php
namespace kahlan\spec\suite;

use Exception;
use RuntimeException;
use stdClass;
use DateTime;
use kahlan\Specification;
use kahlan\Matcher;
use kahlan\Expectation;
use kahlan\plugin\Stub;

describe("Expectation", function() {

    beforeEach(function() {
        $this->matchers = Matcher::get();
    });

    afterEach(function() {
        Matcher::reset();
        foreach ($this->matchers as $name => $value) {
            foreach ($value as $for => $class) {
                Matcher::register($name, $class, $for);
            }
        }
    });

    describe("->__call()", function() {

        it("throws an exception when using an undefined matcher name", function() {

            $closure = function() {
                $result = Expectation::expect(true)->toHelloWorld(true);
            };

            expect($closure)->toThrow(new Exception("Unexisting matcher attached to `'toHelloWorld'`."));

        });

        it("throws an exception when a specific class matcher doesn't match", function() {

            Matcher::register('toEqualCustom', Stub::classname(['extends' => 'kahlan\matcher\ToEqual']), 'stdClass');

            $closure = function() {
                $result = Expectation::expect([])->toEqualCustom(new stdClass());
            };

            expect($closure)->toThrow(new Exception("Unexisting matcher attached to `'toEqualCustom'` for `stdClass`."));

        });

        it("doesn't wait when the spec passes", function () {

            $start = microtime(true);
            $result = Expectation::expect(true, 1)->toBe(true);
            $end = microtime(true);
            expect($end - $start)->toBeLessThan(1);

        });

        it("loops until the timeout is reached on failure", function () {

            $start = microtime(true);
            $result = Expectation::expect(true, 0.1)->toBe(false);
            $end = microtime(true);
            expect($end - $start)->toBeGreaterThan(0.1);
            expect($end - $start)->toBeLessThan(0.2);

        });

        it("loops until the timeout is reached on failure using a sub spec with a return value", function () {

            $start = microtime(true);
            $subspec = new Specification(['closure' => function() {
                return true;
            }]);
            $result = Expectation::expect($subspec, 0.1)->toBe(false);
            $end = microtime(true);
            expect($end - $start)->toBeGreaterThan(0.1);
            expect($end - $start)->toBeLessThan(0.2);

        });

        it("doesn't wait on failure when a negative expectation is expected", function () {

            $start = microtime(true);
            $result = Expectation::expect(true, 1)->not->toBe(false);
            $end = microtime(true);
            expect($end - $start)->toBeLessThan(1);

        });

    });

    describe("->run()", function() {

        it ("returns the matcher when called", function() {

            $result = Expectation::expect(true)->run();
            expect($result)->toBeAnInstanceOf(Expectation::class);

        });

        it ("runs sub specs", function() {

            $subspec = new Specification(['closure' => function() {
                return true;
            }]);
            $result = Expectation::expect($subspec)->run();
            expect($result)->toBeAnInstanceOf(Expectation::class);

        });

        it("loops until the timeout is reached on failure using a sub spec", function () {

            $start = microtime(true);
            $subspec = new Specification(['closure' => function() {
                expect(true)->toBe(false);
            }]);
            $result = Expectation::expect($subspec, 0.1)->run();
            expect($result)->toBeAnInstanceOf(Expectation::class);
            $end = microtime(true);
            expect($end - $start)->toBeGreaterThan(0.1);
            expect($end - $start)->toBeLessThan(0.2);

        });

    });

    describe("->__get()", function() {

        it("sets the not value using `'not'`", function() {

            $expectation = new Expectation();
            expect($expectation->not())->toBe(false);
            expect($expectation->not)->toBe($expectation);
            expect($expectation->not())->toBe(true);

        });

        it("throws an exception with unsupported attributes", function() {

            $closure = function() {
                $expectation = new Expectation();
                $expectation->abc;
            };
            expect($closure)->toThrow(new Exception('Unsupported attribute `abc`.'));

        });

    });

    describe("->clear()", function() {

        it("clears an expectation", function() {

            $actual = new stdClass();
            $expectation = Expectation::expect($actual, 10);
            $matcher = $expectation->not->toReceive('helloWorld');

            expect($expectation->actual())->toBe($actual);
            expect($expectation->deferred())->toHaveLength(1);
            expect($expectation->timeout())->toBe(10);
            expect($expectation->not())->toBe(true);
            expect($expectation->passed())->toBe(true);
            expect($expectation->logs())->toHaveLength(1);

            $expectation->clear();

            expect($expectation->actual())->toBe(null);
            expect($expectation->deferred())->toHaveLength(0);
            expect($expectation->timeout())->toBe(-1);
            expect($expectation->not())->toBe(false);
            expect($expectation->passed())->toBe(true);
            expect($expectation->logs())->toHaveLength(0);


        });

    });

});
