<?php
namespace kahlan\spec\suite;

use Exception;
use RuntimeException;
use stdClass;
use DateTime;
use kahlan\Specification;
use kahlan\Matcher;
use kahlan\plugin\Stub;

describe("Matcher", function() {

    beforeEach(function() {
        $this->matchers = Matcher::get();
    });

    afterEach(function() {
        foreach ($this->matchers as $name => $value) {
            foreach ($value as $for => $class) {
                Matcher::register($name, $class, $for);
            }
        }
    });

    describe("->__call()", function() {

        it("throws an exception when using an undefined matcher name", function() {

            $closure = function() {
                $matcher = new Matcher();
                $result = $matcher->expect(true)->toHelloWorld(true);
            };

            expect($closure)->toThrow(new Exception('Error, undefined matcher `toHelloWorld`.'));

        });

        it("throws an exception when a specific class matcher doesn't match", function() {

            Matcher::register('toEqualCustom', Stub::classname(['extends' => 'kahlan\matcher\ToEqual']), 'stdClass');

            $closure = function() {
                $matcher = new Matcher();
                $result = $matcher->expect([])->toEqualCustom(new stdClass());
            };

            expect($closure)->toThrow(new Exception('Error, undefined matcher `toEqualCustom` for `stdClass`.'));

        });

        it("doesn't wait when the spec passes", function () {

            $start = microtime(true);
            $matcher = new Matcher();
            $result = $matcher->expect(true, 1)->toBe(true);
            $end = microtime(true);
            expect($end - $start)->toBeLessThan(1);

        });

        it("loops until the timeout is reached on failure", function () {

            $start = microtime(true);
            $matcher = new Matcher();
            $result = $matcher->expect(true, 0.1)->toBe(false);
            $end = microtime(true);
            expect($end - $start)->toBeGreaterThan(0.1);
            expect($end - $start)->toBeLessThan(0.2);

        });

        it("doesn't wait on failure when a negative expectation is expected", function () {

            $start = microtime(true);
            $matcher = new Matcher();
            $result = $matcher->expect(true, 1)->not->toBe(false);
            $end = microtime(true);
            expect($end - $start)->toBeLessThan(1);

        });

    });

    describe("::register()", function() {

        it("registers a matcher", function() {

            Matcher::register('toBeOrNotToBe', Stub::classname(['extends' => 'kahlan\matcher\ToBe']));
            expect(Matcher::exists('toBeOrNotToBe'))->toBe(true);
            expect(Matcher::exists('toBeOrNot'))->toBe(false);

            expect(true)->toBeOrNotToBe(true);

        });

        it("registers a matcher for a specific class", function() {

            Matcher::register('toEqualCustom', Stub::classname(['extends' => 'kahlan\matcher\ToEqual']), 'stdClass');
            expect(Matcher::exists('toEqualCustom', 'stdClass'))->toBe(true);
            expect(Matcher::exists('toEqualCustom'))->toBe(false);

            expect(new stdClass())->toEqualCustom(new stdClass());
            expect(new stdClass())->not->toEqualCustom(new DateTime());

        });

        it("makes registered matchers for a specific class available for sub classes", function() {

            Matcher::register('toEqualCustom', Stub::classname(['extends' => 'kahlan\matcher\ToEqual']), 'Exception');
            expect(Matcher::exists('toEqualCustom', 'Exception'))->toBe(true);
            expect(Matcher::exists('toEqualCustom'))->toBe(false);

            expect(new RuntimeException())->toEqualCustom(new RuntimeException());

        });

    });

    describe("::get()", function() {

        it("returns all registered matchers", function() {

            Matcher::reset();
            Matcher::register('toBe', 'kahlan\matcher\ToBe');

            expect(Matcher::get())->toBe([
                'toBe' => ['' => 'kahlan\matcher\ToBe']
            ]);

        });

        it("returns a registered matcher", function() {

            expect(Matcher::get('toBe'))->toBe('kahlan\matcher\ToBe');

        });

        it("returns the default registered matcher", function() {

            expect(Matcher::get('toBe', 'stdClass'))->toBe('kahlan\matcher\ToBe');

        });

        it("returns a custom matcher when defined for a specific class", function() {

            Matcher::register('toBe', 'kahlan\matcher\ToEqual', 'stdClass');

            expect(Matcher::get('toBe', 'DateTime'))->toBe('kahlan\matcher\ToBe');
            expect(Matcher::get('toBe', 'stdClass'))->toBe('kahlan\matcher\ToEqual');

        });

    });

    describe("::unregister()", function() {

        it("unregisters a matcher", function() {

            Matcher::register('toBeOrNotToBe', Stub::classname(['extends' => 'kahlan\matcher\ToBe']));
            expect(Matcher::exists('toBeOrNotToBe'))->toBe(true);

            Matcher::unregister('toBeOrNotToBe');
            expect(Matcher::exists('toBeOrNotToBe'))->toBe(false);

        });

        it("unregisters all matchers", function() {

            expect(Matcher::get())->toBeGreaterThan(1);
            Matcher::unregister(true);
            Matcher::register('toHaveLength', 'kahlan\matcher\ToHaveLength');
            expect(Matcher::get())->toHaveLength(1);

        });

    });

    describe("::reset()", function() {

         it("unregisters all matchers", function() {

            expect(Matcher::get())->toBeGreaterThan(1);
            Matcher::reset();
            Matcher::register('toHaveLength', 'kahlan\matcher\ToHaveLength');
            expect(Matcher::get())->toHaveLength(1);

        });

    });

});