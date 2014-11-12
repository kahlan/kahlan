<?php
namespace spec\kahlan\reporter\coverage;

use kahlan\reporter\coverage\Collector;
use kahlan\reporter\coverage\driver\Xdebug;
use kahlan\reporter\coverage\exporter\Scrutinizer;
use spec\fixture\reporter\coverage\exporter\NoEmptyLine;
use spec\fixture\reporter\coverage\exporter\ExtraEmptyLine;

describe("Scrutinizer", function() {

    describe("::export()", function() {

        it("exports the coverage of a file with no extra end line", function() {

            $path = 'spec/fixture/reporter/coverage/exporter/NoEmptyLine.php';

            $collector = new Collector([
                'driver'    => new Xdebug(),
                'path'      => $path
            ]);

            $code = new NoEmptyLine();

            $collector->start();
            $code->shallNotPass();
            $collector->stop();

            $time = time();

            $xml = Scrutinizer::export([
                'collector' => $collector,
                'time'      => $time
            ]);

$expected = <<<EOD
<?xml version="1.0" encoding="UTF-8"?>
<coverage generated="{$time}">
  <project timestamp="{$time}">
    <file name="spec/fixture/reporter/coverage/exporter/NoEmptyLine.php">
      <line num="8" type="stmt" count="1"/>
      <line num="10" type="stmt" count="0"/>
      <line num="12" type="stmt" count="1"/>
      <line num="13" type="stmt" count="0"/>
    </file>
    <metrics loc="9" ncloc="5" statements="4" coveredstatements="2"/>
  </project>
</coverage>

EOD;

            expect($xml)->toBe($expected);
        });

        it("exports the coverage of a file with an extra line at the end", function() {

            $path = 'spec/fixture/reporter/coverage/exporter/ExtraEmptyLine.php';

            $collector = new Collector([
                'driver'    => new Xdebug(),
                'path'      => $path
            ]);

            $code = new ExtraEmptyLine();

            $collector->start();
            $code->shallNotPass();
            $collector->stop();

            $time = time();

            $xml = Scrutinizer::export([
                'collector' => $collector,
                'time'      => $time
            ]);

$expected = <<<EOD
<?xml version="1.0" encoding="UTF-8"?>
<coverage generated="{$time}">
  <project timestamp="{$time}">
    <file name="spec/fixture/reporter/coverage/exporter/ExtraEmptyLine.php">
      <line num="8" type="stmt" count="1"/>
      <line num="10" type="stmt" count="0"/>
      <line num="12" type="stmt" count="1"/>
      <line num="13" type="stmt" count="0"/>
    </file>
    <metrics loc="9" ncloc="5" statements="4" coveredstatements="2"/>
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

            $path = 'spec/fixture/reporter/coverage/exporter/NoEmptyLine.php';

            $collector = new Collector([
                'driver'    => new Xdebug(),
                'path'      => $path
            ]);

            $code = new NoEmptyLine();

            $collector->start();
            $code->shallNotPass();
            $collector->stop();

            $time = time();

            $success = Scrutinizer::write([
                'collector' => $collector,
                'file'      => $this->output,
                'time'      => $time
            ]);

            expect($success)->toBe(468);

            $xml = file_get_contents($this->output);

$expected = <<<EOD
<?xml version="1.0" encoding="UTF-8"?>
<coverage generated="{$time}">
  <project timestamp="{$time}">
    <file name="spec/fixture/reporter/coverage/exporter/NoEmptyLine.php">
      <line num="8" type="stmt" count="1"/>
      <line num="10" type="stmt" count="0"/>
      <line num="12" type="stmt" count="1"/>
      <line num="13" type="stmt" count="0"/>
    </file>
    <metrics loc="9" ncloc="5" statements="4" coveredstatements="2"/>
  </project>
</coverage>

EOD;

            expect($xml)->toBe($expected);

        });

    });

});
