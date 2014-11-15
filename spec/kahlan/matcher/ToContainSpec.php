<?php
namespace spec\kahlan\matcher;

use kahlan\matcher\ToContain;

describe("toContain", function() {

    describe("::match()", function() {

        it("passes if 3 is in [1, 2, 3]", function() {

            expect([1, 2, 3])->toContain(3);

        });

        it("passes if 'a' is in ['a', 'b', 'c']", function() {

            expect(['a', 'b', 'c'])->toContain('a');

        });

        it("passes if 'd' is in ['a', 'b', 'c']", function() {

            expect(['a', 'b', 'c'])->not->toContain('d');

        });

    });

    describe("::description()", function() {

        it("returns the description message", function() {

            $report['params'] = [
                'actual'   => [1, 2, 3],
                'expected' => 4
            ];

            $actual = ToContain::description($report);

            expect($actual)->toBe('contain expected.');

        });

    });

});
