<?php
namespace kahlan\spec\suite;

use Exception;
use kahlan\Spec;

describe("Spec", function() {

    beforeEach(function() {

        $this->spec = new Spec([
            'message' => 'runs a spec',
            'closure' => function() {}
        ]);

    });

    describe("__construct", function() {

        it("throws an exception with invalid closure", function() {

            $closure = function() {
                $this->spec = new Spec([
                    'message' => 'runs a spec',
                    'closure' => null
                ]);
            };

            expect($closure)->toThrow(new Exception('Error, invalid closure.'));

        });

    });


});