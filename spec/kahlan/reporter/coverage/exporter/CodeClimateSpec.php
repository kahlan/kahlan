<?php
namespace spec\kahlan\reporter\coverage;

use kahlan\reporter\coverage\Collector;
use kahlan\reporter\coverage\driver\Xdebug;
use kahlan\reporter\coverage\exporter\CodeClimate;
use spec\fixture\reporter\coverage\NoEmptyLine;
use spec\fixture\reporter\coverage\ExtraEmptyLine;

describe("CodeClimate", function() {

    beforeEach(function() {
        if (!extension_loaded('xdebug')) {
            skipIf(true);
        }
    });

    describe("::export()", function() {

        it("exports custom parameters", function() {
            $collector = new Collector([
                'driver'    => new Xdebug()
            ]);

            $json = CodeClimate::export([
                'collector'      => $collector,
                'repo_token'     => 'ABC',
                'ci'             => [
                    'name'             => 'kahlan-ci',
                    'build_identifier' => '123'
                ]
            ]);

            $actual = json_decode($json, true);

            unset($actual['run_at']);
            expect($actual['ci'])->toBe([
                'name'             => 'kahlan-ci',
                'build_identifier' => '123'
            ]);
            expect($actual['repo_token'])->toBe('ABC');
        });

        it("exports the coverage of a file with no extra end line", function() {

            $path = 'spec/fixture/reporter/coverage/NoEmptyLine.php';

            $collector = new Collector([
                'driver'    => new Xdebug(),
                'path'      => $path
            ]);

            $code = new NoEmptyLine();

            $collector->start();
            $code->shallNotPass();
            $collector->stop();

            $json = CodeClimate::export([
                'collector'      => $collector,
                'repo_token'     => 'ABC'
            ]);

            $actual = json_decode($json, true);

            $coverage = $actual['source_files'][0];
            expect($coverage['name'])->toBe($path);
            expect($coverage['coverage'])->toHaveLength(15);
            expect(array_filter($coverage['coverage']))->toHaveLength(2);

            expect(array_filter($coverage['coverage'], function($value){
                return $value === 0;
            }))->toHaveLength(2);

            expect(array_filter($coverage['coverage'], function($value){
                return $value === null;
            }))->toHaveLength(11);

        });

        it("exports the coverage of a file with an extra line at the end", function() {

            $path = 'spec/fixture/reporter/coverage/ExtraEmptyLine.php';

            $collector = new Collector([
                'driver'    => new Xdebug(),
                'path'      => $path
            ]);

            $code = new ExtraEmptyLine();

            $collector->start();
            $code->shallNotPass();
            $collector->stop();

            $json = CodeClimate::export([
                'collector'      => $collector,
                'repo_token'     => 'ABC',
                'ci'             => [
                    'name'             => 'kahlan-ci',
                    'build_identifier' => '123'
                ]
            ]);

            $actual = json_decode($json, true);

            $coverage = $actual['source_files'][0];
            expect($coverage['name'])->toBe($path);
            expect($coverage['coverage'])->toHaveLength(16);

            expect(array_filter($coverage['coverage'], function($value){
                return $value === 0;
            }))->toHaveLength(2);

            expect(array_filter($coverage['coverage'], function($value){
                return $value === null;
            }))->toHaveLength(12);

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

            $path = 'spec/fixture/reporter/coverage/ExtraEmptyLine.php';

            $collector = new Collector([
                'driver'    => new Xdebug(),
                'path'      => $path
            ]);

            $code = new ExtraEmptyLine();

            $collector->start();
            $code->shallNotPass();
            $collector->stop();

            $success = CodeClimate::write([
                'collector'      => $collector,
                'file'           => $this->output,
                'environment'    => [
                    'pwd'        => '/home/crysalead/kahlan'
                ],
                'repo_token'     => 'ABC'
            ]);

            expect($success)->toBe(460);

            $json = file_get_contents($this->output);
            $actual = json_decode($json, true);

            $coverage = $actual['source_files'][0];
            expect($coverage['name'])->toBe($path);
            expect($coverage['coverage'])->toHaveLength(16);

        });

    });

});
