<?php
namespace kahlan\spec\suite\matcher;

use stdClass;
use kahlan\matcher\ToBeAnInstanceOf;

describe("toBeAnInstanceOf", function() {

    describe("::match()", function() {

        it("passes if an instance of stdClass is an object", function() {

            expect(new stdClass())->toBeAnInstanceOf('stdClass');

        });

        it("passes if an instance of stdClass is not a Exception", function() {

            expect(new stdClass())->not->toBeAnInstanceOf('Exception');

        });

    });


    describe("::description()", function() {

        it("returns the description message", function() {

            $actual = ToBeAnInstanceOf::description();

            expect($actual)->toBe('be an instance of expected.');

        });

    });

});
