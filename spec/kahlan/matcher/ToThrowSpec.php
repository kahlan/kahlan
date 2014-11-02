<?php
namespace spec\matcher;

use Exception;
use RuntimeException;

describe("toThrow", function() {

    describe("::match()", function() {

        it("catches any kind of exception", function() {

            $closure = function() {
                throw new RuntimeException();
            };
            expect($closure)->toThrow();

            $closure = function() {
                throw new Exception('exception message');
            };
            expect($closure)->toThrow();

        });

        it("catches any kind of exception but with a specific code", function() {

            $closure = function() {
                throw new RuntimeException('runtime error', 500);
            };
            expect($closure)->toThrow(null, 500);

            $closure = function() {
                throw new Exception('exception message', 500);
            };
            expect($closure)->toThrow(null, 500);

        });

        it("catches a detailed exception", function() {

            $closure = function() {
                throw new RuntimeException('exception message');
            };
            expect($closure)->toThrow(new RuntimeException('exception message'));

        });

        it("catches a detailed exception with some specific code", function() {

            $closure = function() {
                throw new RuntimeException('exception message', 500);
            };
            expect($closure)->not->toThrow(new RuntimeException('exception message'));
            expect($closure)->toThrow(new RuntimeException('exception message', 500));

        });

        it("catches a detailed exception using the message name only", function() {

            $closure = function() {
                throw new RuntimeException('exception message');
            };
            expect($closure)->toThrow('exception message');

        });

        it("doesn't catch whatever exception if a detailed one is expected", function() {

            $closure = function() {
                throw new RuntimeException();
            };
            expect($closure)->not->toThrow(new RuntimeException('exception message'));

        });

        it("doesn't catch the exception if the expected exception has a different class name", function() {

            $closure = function() {
                throw new Exception('exception message');
            };
            expect($closure)->not->toThrow(new RuntimeException('exception message'));

            $closure = function() {
                throw new RuntimeException('exception message');
            };
            expect($closure)->not->toThrow(new Exception('exception message'));

        });

    });

});
