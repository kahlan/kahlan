<?php
namespace kahlan\spec\suite;

use Exception;
use kahlan\Arg;

describe("Arg", function() {

    describe("::__callStatic()", function() {

        it("creates matcher", function() {

            $arg = Arg::toBe(true);
            expect($arg->match(true))->toBe(true);
            expect($arg->match(true))->not->toBe(false);

        });

        it("creates a negative matcher", function() {

            $arg = Arg::notToBe(true);
            expect($arg->match(true))->not->toBe(true);
            expect($arg->match(true))->toBe(false);

        });

        it("throws an exception using an undefined matcher name", function() {

            $closure = function() {
                $arg = Arg::toHelloWorld(true);
            };
            expect($closure)->toThrow(new Exception("Unexisting matcher attached to `'toHelloWorld'`."));

        });

    });

});