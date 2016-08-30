<?php
namespace Kahlan\Spec\Suite\Plugin\Call;

use Kahlan\Plugin\Call\MethodCalls;

describe("MethodCalls", function() {

    beforeEach(function() {
        MethodCalls::reset();
    });

    describe("::log()", function() {

        it("logs a dynamic call", function() {

            MethodCalls::log('my\name\space\Class', [
                'name' => 'methodName'
            ]);

            $logs = MethodCalls::logs();

            expect($logs[0][0])->toEqual([
                'class'    => 'my\name\space\Class',
                'name'     => 'methodName',
                'instance' => null,
                'static'   => false
            ]);

        });

        it("logs a static call", function() {

            MethodCalls::log('my\name\space\Class', [
                'name' => '::methodName'
            ]);

            $logs = MethodCalls::logs();

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

            $index = MethodCalls::lastFindIndex(100);
            expect($index)->toBe(100);

            $index = MethodCalls::lastFindIndex();
            expect($index)->toBe(100);

        });

    });

});
