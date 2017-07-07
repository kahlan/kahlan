<?php
namespace Kahlan\Spec\Suite;

use InvalidArgumentException;
use Kahlan\ExternalExpectation;
use RuntimeException;

function externalExpectation($callback, $type = 'Exception')
{
    return new ExternalExpectation(compact('callback', 'type'));
}

describe("ExternalExpectation", function () {

    describe("->process()", function () {

        it("should handle passes", function () {

            $actual = externalExpectation(function () {});
            expect($actual->process())->toBe(true);

            $logs = $actual->logs();
            expect($logs)->toHaveLength(1);
            expect($logs[0])->toBe(['type' => 'passed']);

        });

        it("should handle failures", function () {

            $expected = new RuntimeException('Failure description.');
            $actual = externalExpectation(function () use ($expected) {
                throw $expected;
            });
            expect($actual->process())->toBe(false);

            $logs = $actual->logs();
            expect($logs)->toHaveLength(1);
            expect($logs[0]['type'])->toBe('failed');
            expect($logs[0]['data'])->toBe(['external' => true, 'description' => $expected->getMessage()]);
            expect($logs[0]['backtrace'])->toBe($expected->getTrace());

        });

        it("should handle errors", function () {

            $expected = new InvalidArgumentException();
            $actual = externalExpectation(function () use ($expected) {
                throw $expected;
            }, 'RuntimeException');
            $callback = function () use ($actual) {
                $actual->process();
            };
            expect($callback)->toThrow($expected);
            expect($actual->logs())->toHaveLength(0);

        });

    });

});
