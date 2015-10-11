<?php
namespace kahlan\spec\suite\matcher;

use kahlan\matcher\ToMatchEcho;

describe("toMatchEcho", function() {

    describe("::match()", function() {

        it("passes if `'Hello World!'` is echoed", function() {

            expect(function() { echo 'Hello World!'; })->toMatchEcho('/^H(.*?)!$/');

        });

        it("passes if `'Hello World'` is not echoed", function() {

            expect(function() { echo 'Good Bye!'; })->not->toMatchEcho('/^H(.*?)!$/');

        });

        it("passes if actual match the closure", function() {

            expect(function() { echo 'Hello World!'; })->toMatchEcho(function($actual) {
                return $actual === 'Hello World!';
            });

            expect(function() { echo 'Hello'; })->not->toMatchEcho(function($actual) {
                return $actual === 'Hello World!';
            });

        });

    });

    describe("::description()", function() {

        it("returns the description message", function() {

            ToMatchEcho::match(function() {echo 'Hello';}, "/Bye/");
            $actual = ToMatchEcho::description();

            expect($actual)->toBe([
                'description' => 'matches expected regex in echoed string.',
                'params'      => [
                    "actual"   => "Hello",
                    "expected" => "/Bye/"
                ]
            ]);

        });

    });

});
