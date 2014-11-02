<?php
namespace spec\matcher;

describe("toMatch", function() {

    describe("::match()", function() {

        it("passes if 'Hello World!' match '/^H(?*)!$/'", function() {

            expect('Hello World!')->toMatch('/^H(.*?)!$/');

        });

    });

});
