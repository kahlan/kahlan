<?php
namespace kahlan\spec\suite\reporter\coverage;

use kahlan\reporter\coverage\Collector;
use kahlan\reporter\coverage\driver\Xdebug;
use kahlan\reporter\coverage\driver\Phpdbg;
use kahlan\reporter\coverage\exporter\Lcov;
use kahlan\spec\fixture\reporter\coverage\NoEmptyLine;
use kahlan\spec\fixture\reporter\coverage\ExtraEmptyLine;
use RuntimeException;

describe("Lcov", function() {

    beforeEach(function() {
        if (!extension_loaded('xdebug') && PHP_SAPI !== 'phpdbg') {
            skipIf(true);
        }
        $this->driver = PHP_SAPI !== 'phpdbg' ? new Xdebug() : new Phpdbg();
    });

    describe("::export()", function() {

        it("exports the coverage of a file with no extra end line", function() {

            $path = 'spec' . DS . 'fixture' . DS . 'reporter' . DS . 'coverage' . DS . 'NoEmptyLine.php';

            $collector = new Collector([
                'driver' => $this->driver,
                'path'   => $path
            ]);

            $code = new NoEmptyLine();

            $collector->start();
            $code->shallNotPass();
            $collector->stop();

            $time = time();

            $txt = Lcov::export([
                'collector' => $collector,
                'base_path' => DS . 'home' . DS . 'crysalead' . DS . 'kahlan'
            ]);
            $ds = DS;

$expected = <<<EOD
TN:
SF:/home/crysalead/kahlan/spec/fixture/reporter/coverage/NoEmptyLine.php
1,shallNotPass
FNDA:1,shallNotPass
FNF:1
FNH:1
DA:8,1
DA:10,0
DA:12,1
DA:13,0
LF:4
LH:2
end_of_record
EOD;

            expect($txt)->toBe($expected);
        });

        it("exports the coverage of a file with an extra line at the end", function() {

            $path = 'spec' . DS . 'fixture' . DS . 'reporter' . DS . 'coverage' . DS . 'ExtraEmptyLine.php';

            $collector = new Collector([
                'driver' => $this->driver,
                'path'   => $path
            ]);

            $code = new ExtraEmptyLine();

            $collector->start();
            $code->shallNotPass();
            $collector->stop();

            $time = time();

            $txt = Lcov::export([
                'collector' => $collector,
                'base_path' => DS . 'home' . DS . 'crysalead' . DS . 'kahlan'
            ]);
            $ds = DS;

$expected = <<<EOD
TN:
SF:/home/crysalead/kahlan/spec/fixture/reporter/coverage/ExtraEmptyLine.php
1,shallNotPass
FNDA:1,shallNotPass
FNF:1
FNH:1
DA:8,1
DA:10,0
DA:12,1
DA:13,0
LF:4
LH:2
end_of_record
EOD;

            expect($txt)->toBe($expected);

        });

    });

    describe("::write()", function() {

        beforeEach(function() {
            $this->output = tempnam("/tmp", "KAHLAN");
        });

        afterEach(function() {
            unlink($this->output);
        });

        it("writes the coverage to a file", function() {

            $path = 'spec' . DS . 'fixture' . DS . 'reporter' . DS . 'coverage' . DS . 'NoEmptyLine.php';

            $collector = new Collector([
                'driver' => $this->driver,
                'path'   => $path
            ]);

            $code = new NoEmptyLine();

            $collector->start();
            $code->shallNotPass();
            $collector->stop();

            $time = time();

            $success = Lcov::write([
                'collector' => $collector,
                'file'      => $this->output,
                'base_path' => DS . 'home' . DS . 'crysalead' . DS . 'kahlan'
            ]);

            expect($success)->toBe(178);

            $txt = file_get_contents($this->output);
            $ds = DS;

$expected = <<<EOD
TN:
SF:/home/crysalead/kahlan/spec/fixture/reporter/coverage/NoEmptyLine.php
1,shallNotPass
FNDA:1,shallNotPass
FNF:1
FNH:1
DA:8,1
DA:10,0
DA:12,1
DA:13,0
LF:4
LH:2
end_of_record
EOD;

            expect($txt)->toBe($expected);

        });

        it("throws exception when no file is set", function() {

            expect(function() {
                Lcov::write([]);
            })->toThrow(new RuntimeException('Missing file name'));

        });

    });

});
