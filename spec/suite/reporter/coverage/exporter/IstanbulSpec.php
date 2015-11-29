<?php
namespace kahlan\spec\suite\reporter\coverage;

use kahlan\reporter\coverage\Collector;
use kahlan\reporter\coverage\driver\Xdebug;
use kahlan\reporter\coverage\driver\Phpdbg;
use kahlan\reporter\coverage\exporter\Istanbul;
use kahlan\spec\fixture\reporter\coverage\NoEmptyLine;
use kahlan\spec\fixture\reporter\coverage\ExtraEmptyLine;
use RuntimeException;

describe("Istanbul", function() {

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

            $json = Istanbul::export([
                'collector' => $collector,
                'base_path' => DS . 'home' . DS . 'crysalead' . DS . 'kahlan'
            ]);
            $ds = DS;

$expected = <<<EOD
{"\/home\/crysalead\/kahlan\/spec\/fixture\/reporter\/coverage\/NoEmptyLine.php":{"path":"\/home\/crysalead\/kahlan\/spec\/fixture\/reporter\/coverage\/NoEmptyLine.php","s":{"1":1,"2":0,"3":1,"4":0},"f":{"1":1},"b":[],"statementMap":{"1":{"start":{"line":8,"column":0},"end":{"line":8,"column":31}},"2":{"start":{"line":10,"column":0},"end":{"line":10,"column":34}},"3":{"start":{"line":12,"column":0},"end":{"line":12,"column":30}},"4":{"start":{"line":13,"column":0},"end":{"line":13,"column":30}}},"fnMap":{"1":{"name":"shallNotPass","line":6,"loc":{"start":{"line":6,"column":0},"end":{"line":14,"column":false}}}},"branchMap":[]}}
EOD;

            expect($json)->toBe($expected);
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

            $json = Istanbul::export([
                'collector' => $collector,
                'base_path' => DS . 'home' . DS . 'crysalead' . DS . 'kahlan'
            ]);
            $ds = DS;

$expected = <<<EOD
{"\/home\/crysalead\/kahlan\/spec\/fixture\/reporter\/coverage\/ExtraEmptyLine.php":{"path":"\/home\/crysalead\/kahlan\/spec\/fixture\/reporter\/coverage\/ExtraEmptyLine.php","s":{"1":1,"2":0,"3":1,"4":0},"f":{"1":1},"b":[],"statementMap":{"1":{"start":{"line":8,"column":0},"end":{"line":8,"column":31}},"2":{"start":{"line":10,"column":0},"end":{"line":10,"column":34}},"3":{"start":{"line":12,"column":0},"end":{"line":12,"column":30}},"4":{"start":{"line":13,"column":0},"end":{"line":13,"column":30}}},"fnMap":{"1":{"name":"shallNotPass","line":6,"loc":{"start":{"line":6,"column":0},"end":{"line":14,"column":false}}}},"branchMap":[]}}
EOD;

            expect($json)->toBe($expected);

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

            $success = Istanbul::write([
                'collector' => $collector,
                'file'      => $this->output,
                'base_path' => DS . 'home' . DS . 'crysalead' . DS . 'kahlan'
            ]);

            expect($success)->toBe(635);

            $json = file_get_contents($this->output);
            $ds = DS;

$expected = <<<EOD
{"\/home\/crysalead\/kahlan\/spec\/fixture\/reporter\/coverage\/NoEmptyLine.php":{"path":"\/home\/crysalead\/kahlan\/spec\/fixture\/reporter\/coverage\/NoEmptyLine.php","s":{"1":1,"2":0,"3":1,"4":0},"f":{"1":1},"b":[],"statementMap":{"1":{"start":{"line":8,"column":0},"end":{"line":8,"column":31}},"2":{"start":{"line":10,"column":0},"end":{"line":10,"column":34}},"3":{"start":{"line":12,"column":0},"end":{"line":12,"column":30}},"4":{"start":{"line":13,"column":0},"end":{"line":13,"column":30}}},"fnMap":{"1":{"name":"shallNotPass","line":6,"loc":{"start":{"line":6,"column":0},"end":{"line":14,"column":false}}}},"branchMap":[]}}
EOD;

            expect($json)->toBe($expected);

        });

        it("throws exception when no file is set", function() {

            expect(function() {
                Istanbul::write([]);
            })->toThrow(new RuntimeException('Missing file name'));

        });

    });

});
