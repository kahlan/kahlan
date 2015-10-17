<?php
namespace kahlan\spec\suite\reporter\coverage;

use kahlan\reporter\coverage\Collector;
use kahlan\reporter\coverage\driver\Xdebug;
use kahlan\reporter\coverage\exporter\CodeClimate;
use kahlan\spec\fixture\reporter\coverage\NoEmptyLine;
use kahlan\spec\fixture\reporter\coverage\ExtraEmptyLine;
use RuntimeException;

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
                'collector'    => $collector,
                'repo_token'   => 'ABC',
                'head'         => '1234',
                'branch'       => 'mybranch',
                'committed_at' => '1419462000',
                'ci_service'   => [
                    'name'             => 'kahlan-ci',
                    'build_identifier' => '123'
                ]
            ]);

            $actual = json_decode($json, true);

            expect($actual['repo_token'])->toBe('ABC');

            expect($actual['git'])->toBe([
                'head'         => '1234',
                'branch'       => 'mybranch',
                'committed_at' => '1419462000'
            ]);

            expect($actual['ci_service'])->toBe([
                'name'             => 'kahlan-ci',
                'build_identifier' => '123'
            ]);
        });

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

            $json = CodeClimate::export([
                'collector'  => $collector,
                'repo_token' => 'ABC'
            ]);

            $actual = json_decode($json, true);

            $coverage = $actual['source_files'][0];
            expect($coverage['name'])->toBe($path);

            $coverage = json_decode($coverage['coverage']);
            expect($coverage)->toHaveLength(15);

            expect(array_filter($coverage))->toHaveLength(2);

            expect(array_filter($coverage, function($value){
                return $value === 0;
            }))->toHaveLength(2);

            expect(array_filter($coverage, function($value){
                return $value === null;
            }))->toHaveLength(11);

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

            $json = CodeClimate::export([
                'collector'  => $collector,
                'repo_token' => 'ABC',
                'ci'         => [
                    'name'             => 'kahlan-ci',
                    'build_identifier' => '123'
                ]
            ]);

            $actual = json_decode($json, true);

            $coverage = $actual['source_files'][0];
            expect($coverage['name'])->toBe($path);

            $coverage = json_decode($coverage['coverage']);
            expect($coverage)->toHaveLength(16);

            expect(array_filter($coverage, function($value){
                return $value === 0;
            }))->toHaveLength(2);

            expect(array_filter($coverage, function($value){
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

            $path = 'spec' . DS . 'fixture' . DS . 'reporter' . DS . 'coverage' . DS . 'ExtraEmptyLine.php';

            $collector = new Collector([
                'driver' => new Xdebug(),
                'path'   => $path
            ]);

            $code = new ExtraEmptyLine();

            $collector->start();
            $code->shallNotPass();
            $collector->stop();

            $success = CodeClimate::write([
                'collector'   => $collector,
                'file'        => $this->output,
                'environment' => [
                    'pwd'     => DS . 'home' . DS . 'crysalead' . DS . 'kahlan'
                ],
                'repo_token'  => 'ABC'
            ]);

            $json = file_get_contents($this->output);
            expect($success)->toBe(strlen($json));

            $actual = json_decode($json, true);

            $coverage = $actual['source_files'][0];
            expect($coverage['name'])->toBe($path);
            $coverage = json_decode($coverage['coverage']);
            expect($coverage)->toHaveLength(16);

        });

        it("throws an exception when no file is set", function() {

            expect(function() {
                CodeClimate::write([]);
            })->toThrow(new RuntimeException("Missing file name"));

        });

    });

});
