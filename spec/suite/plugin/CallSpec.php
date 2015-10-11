<?php
namespace kahlan\spec\suite\plugin;

use kahlan\plugin\Call;

describe("Call", function() {

    beforeEach(function() {
        Call::reset();
    });

    describe("::log()", function() {

        it("logs a dynamic call", function() {

            Call::log('my\name\space\Class', [
                'name' => 'methodName'
            ]);

            $logs = Call::logs();

            expect($logs[0][0])->toEqual([
                'class'    => 'my\name\space\Class',
                'name'     => 'methodName',
                'instance' => null,
                'static'   => false
            ]);

        });

        it("logs a static call", function() {

            Call::log('my\name\space\Class', [
                'name' => '::methodName'
            ]);

            $logs = Call::logs();

            expect($logs[0][0])->toEqual([
                'class'    => 'my\name\space\Class',
                'name'     => 'methodName',
                'instance' => null,
                'static'   => true
            ]);

        });

    });

    describe("::lastFindIndex()", function() {

        it("gets/sets the last find index", function() {

            $index = Call::lastFindIndex(100);
            expect($index)->toBe(100);

            $index = Call::lastFindIndex();
            expect($index)->toBe(100);

        });

    });

});
