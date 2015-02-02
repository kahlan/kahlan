<?php
namespace kahlan\spec\suite\matcher;

use kahlan\matcher\ToHaveLength;

describe("toHaveLength", function() {

    describe("::match()", function() {

        it("passes if 'Hello World' has a length of 11", function() {

            expect('Hello World')->toHaveLength(11);

        });

        it("passes if [1, 3, 7] has a length of 3", function() {

            expect([1, 3, 7])->toHaveLength(3);

        });

        it("passes if [] has a length of 0", function() {

            expect([])->toHaveLength(0);

        });

    });

    describe("::description()", function() {

        it("returns the description message", function() {

            ToHaveLength::match([1, 2, 3], 5);
            $actual = ToHaveLength::description();

            expect($actual['description'])->toBe('have the expected length.');
            expect($actual['params']['actual'])->toBe([1, 2, 3]);
            expect($actual['params']['actual length'])->toBe(3);
            expect($actual['params']['expected length'])->toBe(5);

        });

    });

});
