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

        it("returns the global metrics", function() {

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
                'loc'      => 31,
                'nlloc'    => 23,
                'lloc'     => 8,
                'cloc'     => 4,
                'coverage' => 4,
                'methods'  => 2,
                'cmethods' => 2,
                'percent'  => 50
            ]);

            foreach ($this->path as $path) {
                $path = realpath($path);
                expect(isset($files[$path]))->toBe(true);
            }
        });

        it("returns class metrics", function() {

            $code = new ExtraEmptyLine();

            $this->collector->start();
            $code->shallNotPass();
            $this->collector->stop();

            $metrics = $this->collector->metrics();

            $actual = $metrics->get('spec\fixture\reporter\coverage\ExtraEmptyLine')->data();

            $files = $actual['files'];
            unset($actual['files']);

            expect($actual)->toBe([
                'loc'      => 11,
                'nlloc'    => 7,
                'lloc'     => 4,
                'cloc'     => 2,
                'coverage' => 2,
                'methods'  => 1,
                'cmethods' => 1,
                'percent'  => 50
            ]);

            $path = realpath('spec/fixture/reporter/coverage/ExtraEmptyLine.php');
            expect(isset($files[$path]))->toBe(true);
        });

        it("returns function metrics", function() {

            $code = new ExtraEmptyLine();

            $this->collector->start();
            $code->shallNotPass();
            $this->collector->stop();

            $metrics = $this->collector->metrics();

            $actual = $metrics->get('spec\fixture\reporter\coverage\ExtraEmptyLine::shallNotPass()')->data();

            $files = $actual['files'];
            unset($actual['files']);

            expect($actual)->toBe([
                'loc'      => 8,
                'nlloc'    => 4,
                'lloc'     => 4,
                'cloc'     => 2,
                'coverage' => 2,
                'methods'  => 1,
                'cmethods' => 1,
                'line'     => [
                    'start' => 5,
                    'stop'  => 13
                ],
                'percent'  => 50
            ]);

            $path = realpath('spec/fixture/reporter/coverage/ExtraEmptyLine.php');
            expect(isset($files[$path]))->toBe(true);
        });

    });

});
