<?php
namespace kahlan\spec\suite\util;

use Exception;
use InvalidArgumentException;
use kahlan\util\Timeout;

declare(ticks = 1);

describe("Timeout", function() {

    describe("::run()", function() {

        it("runs the passed closure", function () {

            $start = microtime(true);

            expect(Timeout::run(function() {return true;}, 1))->toBe(true);

            $end = microtime(true);
            expect($end - $start)->toBeLessThan(1);

        });

        it("throws an exception if an invalid closure is provided", function() {

            $closure = function() {
                Timeout::run("invalid", 1);
            };

            expect($closure)->toThrow(new InvalidArgumentException());

        });

        it("throws an exception on timeout", function() {

            $start = microtime(true);

            $closure = function() {
                Timeout::run(function() {
                    while(true) sleep(1);
                }, 1);
            };

            expect($closure)->toThrow(new Exception('Timeout reached, execution aborted after 1 second(s).'));

            $end = microtime(true);
            expect($end - $start)->toBeGreaterThan(1);

        });

    });

    describe("::spin()", function() {

        it("runs the passed closure", function () {

            $start = microtime(true);

            expect(Timeout::spin(function() {return true;}, 1))->toBe(true);

            $end = microtime(true);
            expect($end - $start)->toBeLessThan(1);

        });

        it("throws an exception if an invalid closure is provided", function() {

            $closure = function() {
                Timeout::spin("invalid", 1);
            };

            expect($closure)->toThrow(new InvalidArgumentException());

        });

        it("throws an exception on timeout", function() {

            $start = microtime(true);

            $closure = function() {
                Timeout::spin(function() {}, 1);
            };

            expect($closure)->toThrow(new Exception('Timeout reached, execution aborted after 1 second(s).'));

            $end = microtime(true);
            expect($end - $start)->toBeGreaterThan(1);

        });

    });

});