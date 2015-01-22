<?php
namespace kahlan\spec\suite\matcher;

use kahlan\matcher\ToBeLessThan;

describe("toBeLessThan", function() {

    describe("::match()", function() {

        it("passes if 1 is < 2", function() {

            expect(1)->toBeLessThan(2);

        });

        it("passes if 0.999 < 1", function() {

            expect(0.999)->toBeLessThan(1);

        });

        it("passes if 2 is not < 2", function() {

            expect(2)->not->toBeLessThan(2);

        });

    });

    describe("::description()", function() {

        it("returns the description message", function() {

            $report['params'] = [
                'actual'   => 2,
                'expected' => 1
            ];

            $actual = ToBeLessThan::description($report);

            expect($actual)->toBe('be lesser than expected.');

        });

    });

});
