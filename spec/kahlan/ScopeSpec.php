<?php
namespace spec;

describe("Scope", function() {

    describe("__get/__set", function() {
        it("defines a value in the current scope", function() {
            $this->foo = 2;
            expect($this->foo)->toEqual(2);
        });

        it("is not influenced by the previous spec", function() {
            expect(isset($this->foo))->toBe(false);
        });

        context("when nested", function() {

            beforeEach(function() {
                $this->bar = 1;
            });

            it("can access variable from the parent scope", function() {
                expect($this->bar)->toBe(1);
            });
        });
    });

    describe("skipIf", function() {

        $executed = 0;

        context("when used in a scope", function() use (&$executed) {

            before(function() {
                skipIf(true);
            });

            it("skips this spec", function() use (&$executed) {
                expect(true)->toBe(false);
                $executed++;
            });

            it("skips this spec too", function() use (&$executed) {
                expect(true)->toBe(false);
                $executed++;
            });

        });

        it("expects that no spec have been runned", function() use (&$executed) {
            expect($executed)->toBe(0);
        });

        context("when used in a spec", function() use (&$executed) {

            it("skips this spec", function() use (&$executed) {
                skipIf(true);
                expect(true)->toBe(false);
                $executed++;
            });

            it("doesn't skip this spec", function() use (&$executed) {
                $executed++;
            });

        });

        it("expects that only one test have been runned", function() use (&$executed) {
            expect($executed)->toBe(1);
        });

    });

    describe("__call", function() {

        $this->customMethod = function($self) {
            $self->called = true;
            return 'called';
        };

        it("calls closure assigned to scope property to be inkovable", function() {
            $actual = $this->customMethod($this);
            expect($actual)->toBe('called');
            expect($this->called)->toBe(true);
        });

    });

});
?>