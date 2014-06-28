<?php
namespace spec\matcher;

use stdClass;

describe("toEcho::match", function() {

    it("passes if `'Hello World!'` is echoed", function() {
        expect(function() { echo 'Hello World!'; })->toEcho('Hello World!');
    });

    it("passes if `'Hello World'` is not echoed", function() {
        expect(function() { echo 'Good Bye!'; })->not->toEcho('Hello World!');
    });
});

?>