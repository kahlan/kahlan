<?php
namespace Kahlan\Spec\Suite\Plugin\Call;

use Kahlan\Plugin\Call\FunctionCalls;

describe("FunctionCalls", function() {

    beforeEach(function() {
        FunctionCalls::reset();
    });

    describe("::log()", function() {

        it("logs a function call", function() {

            FunctionCalls::log('my\name\space\function', [
                'name' => 'value'
            ]);

            $logs = FunctionCalls::logs();

            expect($logs[0])->toEqual([
                'name' => 'my\name\space\function',
                'args' => ['name' => 'value']
            ]);

        });

    });

    describe("::lastFindIndex()", function() {

        it("gets/sets the last find index", function() {

            $index = FunctionCalls::lastFindIndex(100);
            expect($index)->toBe(100);

            $index = FunctionCalls::lastFindIndex();
            expect($index)->toBe(100);

        });

    });

});
