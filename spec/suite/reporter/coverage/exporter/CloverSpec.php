<?php
namespace kahlan\spec\suite\reporter\coverage;

use kahlan\reporter\coverage\Collector;
use kahlan\reporter\coverage\driver\Xdebug;
use kahlan\reporter\coverage\exporter\Clover;
use kahlan\spec\fixture\reporter\coverage\NoEmptyLine;
use kahlan\spec\fixture\reporter\coverage\ExtraEmptyLine;
use RuntimeException;

describe("Clover", function() {

    beforeEach(function() {
        if (!extension_loaded('xdebug')) {
            skipIf(true);
        }
    });

    describe("::export()", function() {

        it("exports the coverage of a file with no extra end line", function() {

            $path = 'spec' . DS . 'fixture' . DS . 'reporter' . DS . 'coverage' . DS . 'NoEmptyLine.php';

            $collector = new Collector([
                'driver' => new Xdebug(),
                'path'   => $path
            ]);

            $code = new NoEmptyLine();

            $collector->start();
            $code->shallNotPass();
            $collector->stop();

            $time = time();

            $xml = Clover::export([
                'collector' => $collector,
                'time'      => $time,
                'base_path' => DS . 'home' . DS . 'crysalead' . DS . 'kahlan'
            ]);
            $ds = DS;

$expected = <<<EOD
<?xml version="1.0" encoding="UTF-8"?>
<coverage generated="{$time}">
  <project timestamp="{$time}">
    <file name="{$ds}home{$ds}crysalead{$ds}kahlan{$ds}spec{$ds}fixture{$ds}reporter{$ds}coverage{$ds}NoEmptyLine.php">
      <line num="8" type="stmt" count="1"/>
      <line num="10" type="stmt" count="0"/>
      <line num="12" type="stmt" count="1"/>
      <line num="13" type="stmt" count="0"/>
    </file>
    <metrics loc="15" ncloc="11" statements="4" coveredstatements="2"/>
  </project>
</coverage>

EOD;

            expect($xml)->toBe($expected);
        });

        it("exports the coverage of a file with an extra line at the end", function() {

            $path = 'spec' . DS . 'fixture' . DS . 'reporter' . DS . 'coverage' . DS . 'ExtraEmptyLine.php';

            $collector = new Collector([
                'driver' => new Xdebug(),
                'path'   => $path
            ]);

            $code = new ExtraEmptyLine();

            $collector->start();
            $code->shallNotPass();
            $collector->stop();

            $time = time();

            $xml = Clover::export([
                'collector' => $collector,
                'time'      => $time,
                'base_path' => DS . 'home' . DS . 'crysalead' . DS . 'kahlan'
            ]);
            $ds = DS;

$expected = <<<EOD
<?xml version="1.0" encoding="UTF-8"?>
<coverage generated="{$time}">
  <project timestamp="{$time}">
    <file name="{$ds}home{$ds}crysalead{$ds}kahlan{$ds}spec{$ds}fixture{$ds}reporter{$ds}coverage{$ds}ExtraEmptyLine.php">
      <line num="8" type="stmt" count="1"/>
      <line num="10" type="stmt" count="0"/>
      <line num="12" type="stmt" count="1"/>
      <line num="13" type="stmt" count="0"/>
    </file>
    <metrics loc="16" ncloc="12" statements="4" coveredstatements="2"/>
  </project>
</coverage>

EOD;

            expect($xml)->toBe($expected);

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
                'driver' => new Xdebug(),
                'path'   => $path
            ]);

            $code = new NoEmptyLine();

            $collector->start();
            $code->shallNotPass();
            $collector->stop();

            $time = time();

            $success = Clover::write([
                'collector' => $collector,
                'file'      => $this->output,
                'time'      => $time,
                'base_path' => DS . 'home' . DS . 'crysalead' . DS . 'kahlan'
            ]);

            expect($success)->toBe(484);

            $xml = file_get_contents($this->output);
            $ds = DS;

$expected = <<<EOD
<?xml version="1.0" encoding="UTF-8"?>
<coverage generated="{$time}">
  <project timestamp="{$time}">
    <file name="{$ds}home{$ds}crysalead{$ds}kahlan{$ds}spec{$ds}fixture{$ds}reporter{$ds}coverage{$ds}NoEmptyLine.php">
      <line num="8" type="stmt" count="1"/>
      <line num="10" type="stmt" count="0"/>
      <line num="12" type="stmt" count="1"/>
      <line num="13" type="stmt" count="0"/>
    </file>
    <metrics loc="15" ncloc="11" statements="4" coveredstatements="2"/>
  </project>
</coverage>

EOD;

            expect($xml)->toBe($expected);

        });

        it("throws exception when no file is set", function() {

            expect(function() {
                Clover::write([]);
            })->toThrow(new RuntimeException('Missing file name'));

        });

    });

});
