<?php
namespace spec\kahlan\matcher;

describe("toMatch", function() {

    describe("::match()", function() {

        it("passes if 'Hello World!' match '/^H(?*)!$/'", function() {

            expect('Hello World!')->toMatch('/^H(.*?)!$/');

        });

        it("passes if actual match the closure", function() {

            expect('Hello World!')->toMatch(function($actual) {
                return $actual === 'Hello World!';
            });

            expect('Hello')->not->toMatch(function($actual) {
                return $actual === 'Hello World!';
            });

        });

    });

});
