<?php
namespace kahlan\spec\suite\matcher;

use stdClass;
use kahlan\matcher\ToEcho;

describe("toEcho", function() {

    describe("::match()", function() {

        it("passes if `'Hello World!'` is echoed", function() {

            expect(function() { echo 'Hello World!'; })->toEcho('Hello World!');

        });

        it("passes if `'Hello World'` is not echoed", function() {

            expect(function() { echo 'Good Bye!'; })->not->toEcho('Hello World!');

        });

    });

    describe("::description()", function() {

        it("returns the description message", function() {

            $report['params'] = [
                'actual'   => function() {
                	echo 'Hello';
                },
                'expected' => 'Good Bye!'
            ];

            $actual = ToEcho::description($report);

            expect($actual)->toBe('echo the expected string.');

        });

    });

});
