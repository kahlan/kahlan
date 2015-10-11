<?php
namespace kahlan\spec\suite;

use Exception;
use kahlan\Specification;
use kahlan\Matcher;

describe("Specification", function() {

    beforeEach(function() {

        $this->spec = new Specification([
            'message' => 'runs a spec',
            'matcher' => new Matcher(),
            'closure' => function() {}
        ]);

    });

    describe("->__construct()", function() {

        it("throws an exception with invalid closure", function() {

            $closure = function() {
                $this->spec = new Specification([
                    'message' => 'runs a spec',
                    'closure' => null
                ]);
            };

            expect($closure)->toThrow(new Exception('Error, invalid closure.'));

        });

    });

    describe("->expect()", function() {

        it("returns the matcher instance", function() {

            $matcher = $this->spec->expect('actual');
            expect($matcher)->toBeAnInstanceOf('kahlan\Matcher');

        });

    });

    describe("->wait()", function() {

        it("returns the matcher instance setted with the correct timeout", function() {

            $matcher = $this->spec->wait('actual', 10);
            expect($matcher)->toBeAnInstanceOf('kahlan\Matcher');
            expect($matcher->timeout())->toBe(10);

            $matcher = $this->spec->expect('actual');
            expect($matcher)->toBeAnInstanceOf('kahlan\Matcher');
            expect($matcher->timeout())->toBe(null);

        });

    });

});