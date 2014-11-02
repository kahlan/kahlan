<?php
namespace spec\kahlan\reporter\coverage;

use kahlan\reporter\coverage\Collector;
use kahlan\reporter\coverage\driver\Xdebug;
use spec\fixture\reporter\coverage\CodeCoverage;

describe("Coverage", function() {

    beforeEach(function() {
        $this->path = 'spec/fixture/reporter/coverage';

        $this->collector = new Collector([
            'driver'    => new Xdebug(),
            'path'      => $this->path
        ]);

        $this->parent = $this->collector;

        $this->child = new Collector([
            'driver'    => new Xdebug(),
            'path'      => $this->path
        ]);

    });

    describe("->export()", function() {

        it("exports covered lines", function() {

            $code = new CodeCoverage();

            $this->collector->start();
            $code->shallPass();
            $this->collector->stop();

            $actual = $this->collector->export();

            expect(array_filter(current($actual)))->toBe([
                16 => 1,
                17 => 1,
                18 => 1,
                19 => 1,
                20 => 1,
                21 => 1
            ]);
        });

        it("exports covered lines and append coverage to parent's coverage data", function() {



            $code = new CodeCoverage();

            $this->parent->start();

            $code->shallNotPass();

            $this->child->start();
            $code->shallPass();
            $this->child->stop();

            $this->parent->stop();

            $actual = $this->child->export();
            expect(array_filter(current($actual)))->toBe([
                16 => 1,
                17 => 1,
                18 => 1,
                19 => 1,
                20 => 1,
                21 => 1
            ]);

            $actual = $this->parent->export();
            expect(array_filter(current($actual)))->toBe([
                6 => 1,
                7 => 1,
                8 => 1,
                11 => 1,
                16 => 1,
                17 => 1,
                18 => 1,
                19 => 1,
                20 => 1,
                21 => 1
            ]);
        });

        it("exports covered lines and doesn't append coverage to parent's coverage data", function() {

            $code = new CodeCoverage();

            $this->parent->start();

            $code->shallNotPass();

            $this->child->start();
            $code->shallPass();
            $this->child->stop(false);

            $this->parent->stop();

            $actual = $this->child->export();
            expect(array_filter(current($actual)))->toBe([
                16 => 1,
                17 => 1,
                18 => 1,
                19 => 1,
                20 => 1,
                21 => 1
            ]);

            $actual = $this->parent->export();
            expect(array_filter(current($actual)))->toBe([
                6 => 1,
                7 => 1,
                8 => 1,
                11 => 1
            ]);
        });

    });

    describe("->start/stop()", function() {

        it("return `true` on success", function() {

            expect($this->collector->start())->toBe(true);
            expect($this->collector->stop())->toBe(true);

        });

    });

    describe("->stop()", function() {

        it("does nothing if not the collector has not been started", function() {

            expect($this->collector->stop())->toBe(false);

        });

        it("does nothing if not the collector has not been started", function() {

            $this->parent->start();
            $this->child->start();

            expect($this->parent->stop())->toBe(false);

            // Required to leave Kahlan in stable state when runned with some coverage reporting.
            $this->child->stop();
            $this->parent->stop();

        });

    });

    describe("->metrics()", function() {

        it("returns the metrics", function() {

            $code = new CodeCoverage();

            $this->collector->start();
            $code->shallPass();
            $this->collector->stop();

            $metrics = $this->collector->metrics();
            expect($metrics)->toBeAnInstanceOf('kahlan\reporter\coverage\Metrics');
        });

    });

});
