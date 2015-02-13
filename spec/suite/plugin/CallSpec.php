<?php
namespace kahlan\spec\suite\plugin;

use kahlan\plugin\Call;

describe("Call", function() {

    beforeEach(function() {

        Call::clear();

    });

    describe("::log()", function() {

        it("should log a call", function() {

            Call::log("reference", [
                'name' => '\My\Namespace\Class'
            ]);

            $logs = Call::logs();
            expect($logs)->toBeA('array');
            expect(isset($logs[0]))->toBeTruthy();
            expect(isset($logs[0][0]))->toBeTruthy();

            $record = $logs[0][0];
            expect(array_keys($record))->toBe(['name', 'instance', 'class', 'static']);
            expect($record['name'])->toBe('\My\Namespace\Class');
            expect($record['class'])->toBe('reference');
            expect($record['static'])->toBe(false);

            Call::clear();

        });

    });

    describe("::lastFindIndex()", function() {

        it("should set index", function() {

            $index = Call::lastFindIndex(100);
            expect($index)->toBe(100);
            $index = Call::lastFindIndex();
            expect($index)->toBe(100);

        });

    });

    after(function() {

        Call::clear();

    });

});
