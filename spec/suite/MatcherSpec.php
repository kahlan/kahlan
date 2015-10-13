<?php
namespace kahlan\spec\suite;

use Exception;
use stdClass;
use DateTime;
use SplMaxHeap;
use kahlan\Specification;
use kahlan\Matcher;
use kahlan\plugin\Stub;

describe("Matcher", function() {

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

            Matcher::register('toEqualCustom', Stub::classname(['extends' => 'kahlan\matcher\ToEqual']), 'SplHeap');
            expect(Matcher::exists('toEqualCustom', 'SplHeap'))->toBe(true);
            expect(Matcher::exists('toEqualCustom'))->toBe(false);

            expect(new SplMaxHeap())->toEqualCustom(new SplMaxHeap());

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

        it("returns all registered matchers for a specific matcher", function() {

            Matcher::register('toBe', 'kahlan\matcher\ToEqual', 'stdClass');

            expect(Matcher::get('toBe', true))->toBe([
                ''         => 'kahlan\matcher\ToBe',
                'stdClass' => 'kahlan\matcher\ToEqual'
            ]);

        });

        it("returns the default registered matcher", function() {

            expect(Matcher::get('toBe', 'stdClass'))->toBe('kahlan\matcher\ToBe');

        });

        it("returns a custom matcher when defined for a specific class", function() {

            Matcher::register('toBe', 'kahlan\matcher\ToEqual', 'stdClass');

            expect(Matcher::get('toBe', 'DateTime'))->toBe('kahlan\matcher\ToBe');
            expect(Matcher::get('toBe', 'stdClass'))->toBe('kahlan\matcher\ToEqual');

        });

        it("throws an exception when using an undefined matcher name", function() {

            $closure = function() {
                Matcher::get('toHelloWorld');
            };

            expect($closure)->toThrow(new Exception("Unexisting default matcher attached to `'toHelloWorld'`."));

        });

        it("throws an exception when using an undefined matcher name for a specific class", function() {

            $closure = function() {
                Matcher::get('toHelloWorld', 'stdClass');
            };

            expect($closure)->toThrow(new Exception("Unexisting matcher attached to `'toHelloWorld'` for `stdClass`."));

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