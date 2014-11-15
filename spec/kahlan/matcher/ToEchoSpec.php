<?php
namespace spec\kahlan\matcher;

use stdClass;

describe("toEcho", function() {

    describe("::match()", function() {

        it("passes if `'Hello World!'` is echoed", function() {

            expect(function() { echo 'Hello World!'; })->toEcho('Hello World!');

        });

        it("passes if `'Hello World'` is not echoed", function() {

            expect(function() { echo 'Good Bye!'; })->not->toEcho('Hello World!');

        });

    });

});
