<?php
use kahlan\cli\Cli;

describe("Cli", function() {
    describe('->color()', function() {
        before(function() {
            $this->check = function($actual, $expected) {
                expect(strlen($actual))->toBe(strlen($expected));

                for ($i=0; $i < strlen($actual); $i++) {
                    $check = (ord($actual[$i]) == ord($expected[$i])) ? true : false;
                    if ($check) break;
                }

                expect($check)->toBe(true);
            };
        });

        it("should return unstyled string when options empty", function() {
            expect(Cli::color("String"))->toBe("String");
        });

        it("should correctly parse a options simple string", function() {
            $this->check(Cli::color("String", "yellow"), "\e[0;33;49mSrting\e[0m");
        });

        it("should correctly parse a options string with divider", function() {
            $this->check(Cli::color("String", "n;yellow;100"), "\e[0;33;110mSrting\e[0m");
        });

        it("should correctly parse a options string with numeric style", function() {
            $this->check(Cli::color("String", "4;red;100"), "\e[0;31;110mSrting\e[0m");
        });

        it("should correctly parse a options string with a numeric color", function() {
            $this->check(Cli::color("String", "n;100;100"), "\e[0;100;100mSrting\e[0m");
        });

        it("should correctly parse light colors", function() {
            $this->check(Cli::color("String", "n;light yellow;100"), "\e[0;133;110mSrting\e[0m");
        });

        it("should correctly parse color with unknown name", function() {
            $this->check(Cli::color("String", "some_strange_color"), "\e[0;39;49mSrting\e[0m");
        });

    });

    describe('->bell()', function() {
        it("should bell", function() {
            expect(function() {
                Cli::bell(2);
            })->toEcho(str_repeat("\007", 2));
        });
    });
});
