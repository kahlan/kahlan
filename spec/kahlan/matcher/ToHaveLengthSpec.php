<?php
namespace spec\kahlan\matcher;

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

});
