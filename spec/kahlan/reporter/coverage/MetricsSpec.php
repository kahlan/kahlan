<?php
namespace spec\kahlan\reporter\coverage;

use kahlan\reporter\coverage\Collector;
use kahlan\reporter\coverage\driver\Xdebug;
use spec\fixture\reporter\coverage\NoEmptyLine;
use spec\fixture\reporter\coverage\ExtraEmptyLine;

describe("Metrics", function() {

    beforeEach(function() {
        $this->path = [
            'spec/fixture/reporter/coverage/ExtraEmptyLine.php',
            'spec/fixture/reporter/coverage/NoEmptyLine.php'
        ];

        $this->collector = new Collector([
            'driver'    => new Xdebug(),
            'path'      => $this->path
        ]);
    });

    describe("->metrics()", function() {

        it("returns the metrics", function() {

            $empty = new ExtraEmptyLine();
            $noEmpty = new NoEmptyLine();

            $this->collector->start();
            $empty->shallNotPass();
            $noEmpty->shallNotPass();
            $this->collector->stop();

            $metrics = $this->collector->metrics();

            $actual = $metrics->data();

            $files = $actual['files'];
            unset($actual['files']);

            expect($actual)->toBe([
                'loc'            => 31,
                'ncloc'          => 23,
                'cloc'           => 8,
                'covered'        => 4,
                'coverage'       => 4,
                'methods'        => 2,
                'coveredMethods' => 2,
                'line'           => 5,
                'percent'        => 50
            ]);

            foreach ($this->path as $path) {
                $path = realpath($path);
                expect(isset($files[$path]))->toBe(true);
            }
        });

    });

});
