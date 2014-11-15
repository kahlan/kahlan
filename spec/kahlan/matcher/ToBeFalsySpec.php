<?php
namespace spec\kahlan\matcher;

use kahlan\matcher\ToBeFalsy;

describe("toBeFalsy", function() {

    describe("::match()", function() {

        it("passes if false is fasly", function() {

            expect(false)->toBeFalsy();

        });

        it("passes if null is fasly", function() {

            expect(null)->toBeFalsy();

        });

        it("passes if [] is fasly", function() {

            expect([])->toBeFalsy();

        });

        it("passes if 0 is fasly", function() {

            expect(0)->toBeFalsy();

        });

        it("passes if '' is fasly", function() {

            expect('')->toBeFalsy();

        });

    });

    describe("::description()", function() {

        it("returns the description message", function() {

            $report['params'] = [
                'actual'   => 2
            ];

            $actual = ToBeFalsy::description($report);

            expect($actual)->toBe('be falsy.');

        });

    });

});
