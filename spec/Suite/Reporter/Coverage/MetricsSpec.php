<?php
namespace Kahlan\Spec\Suite\Reporter\Coverage;

use Kahlan\Reporter\Coverage\Collector;
use Kahlan\Reporter\Coverage\Driver\Xdebug;
use Kahlan\Reporter\Coverage\Driver\Phpdbg;
use Kahlan\Spec\Fixture\Reporter\Coverage\NoEmptyLine;
use Kahlan\Spec\Fixture\Reporter\Coverage\ExtraEmptyLine;
use Kahlan\Spec\Fixture\Reporter\Coverage\ImplementsCoverage;

describe("Metrics", function() {

    beforeEach(function() {
        if (!extension_loaded('xdebug') && PHP_SAPI !== 'phpdbg') {
            skipIf(true);
        }
        $this->driver = PHP_SAPI !== 'phpdbg' ? new Xdebug() : new Phpdbg();
    });

    beforeEach(function() {
        $this->path = [
            'spec/Fixture/Reporter/Coverage/ExtraEmptyLine.php',
            'spec/Fixture/Reporter/Coverage/NoEmptyLine.php'
        ];

        $driver = PHP_SAPI !== 'phpdbg' ? new Xdebug() : new Phpdbg();

        $this->collector = new Collector([
            'driver'    => $this->driver,
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
                'loc'      => 29,
                'nlloc'    => 21,
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

            $actual = $metrics->get('Kahlan\Spec\Fixture\Reporter\Coverage\ExtraEmptyLine')->data();

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

            $path = realpath('spec/Fixture/Reporter/Coverage/ExtraEmptyLine.php');
            expect(isset($files[$path]))->toBe(true);
        });

        it("returns type of metrics", function() {

            $code = new ExtraEmptyLine();

            $this->collector->start();
            $code->shallNotPass();
            $this->collector->stop();

            $metrics = $this->collector->metrics();
            expect($metrics->type())->toBe('namespace');

        });

        it("returns a parent of metrics", function() {

            $code = new ExtraEmptyLine();

            $this->collector->start();
            $code->shallNotPass();
            $this->collector->stop();

            $metrics = $this->collector->metrics();
            expect($metrics->parent())->toBe(null);

        });

        it("returns function metrics", function() {

            $code = new ExtraEmptyLine();

            $this->collector->start();
            $code->shallNotPass();
            $this->collector->stop();

            $metrics = $this->collector->metrics();

            $actual = $metrics->get('Kahlan\Spec\Fixture\Reporter\Coverage\ExtraEmptyLine::shallNotPass()')->data();

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

            $path = realpath('spec/Fixture/Reporter/Coverage/ExtraEmptyLine.php');
            expect(isset($files[$path]))->toBe(true);
        });

        it("return empty on unknown metric", function() {

            $code = new ExtraEmptyLine();

            $this->collector->start();
            $code->shallNotPass();
            $this->collector->stop();

            $metrics = $this->collector->metrics();
            $actual = $metrics->get('some\unknown\name\space');
            expect($actual)->toBe(null);

        });

        it("doesn't store interfaces in metrics", function() {

            $path = [
                'spec/Fixture/Reporter/Coverage/ImplementsCoverage.php',
                'spec/Fixture/Reporter/Coverage/ImplementsCoverageInterface.php'
            ];

            $collector = new Collector([
                'driver'    => $this->driver,
                'path'      => $path
            ]);

            $code = new ImplementsCoverage();

            $collector->start();
            $code->foo();
            $collector->stop();

            $metrics = $collector->metrics();
            $actual = $metrics->get()->data();

            $files = $actual['files'];
            unset($actual['files']);

            expect($actual)->toBe([
                'loc'      => 9,
                'nlloc'    => 8,
                'lloc'     => 1,
                'cloc'     => 1,
                'coverage' => 1,
                'methods'  => 1,
                'cmethods' => 1,
                'percent'  => 100
            ]);

            $path = realpath('spec/Fixture/Reporter/Coverage/ImplementsCoverage.php');
            expect(isset($files[$path]))->toBe(true);

        });

        describe("->children()", function() {

            beforeEach(function() {

                $code = new ExtraEmptyLine();

                $this->collector->start();
                $code->shallNotPass();
                $this->collector->stop();

                $this->metrics = $this->collector->metrics();

            });

            it("returns root's children", function() {

                $children = $this->metrics->children();
                expect(is_array($children))->toBe(true);
                expect(isset($children['Kahlan\\']))->toBe(true);

            });

            it("returns specified child", function() {

                $children = $this->metrics->children('Kahlan\\');
                expect(is_array($children))->toBe(true);
                expect(isset($children['Spec\\']))->toBe(true);

                $children = $this->metrics->children('Kahlan\Spec\\');
                expect(is_array($children))->toBe(true);
                expect(isset($children['Fixture\\']))->toBe(true);

                $children = $this->metrics->children('Kahlan\Spec\Fixture\\');
                expect(is_array($children))->toBe(true);
                expect(isset($children['Reporter\\']))->toBe(true);

                $children = $this->metrics->children('Kahlan\Spec\Fixture\Reporter\\');
                expect(is_array($children))->toBe(true);
                expect(isset($children['Coverage\\']))->toBe(true);

                $children = $this->metrics->children('Kahlan\Spec\Fixture\Reporter\Coverage\\');
                expect(is_array($children))->toBe(true);
                expect(isset($children['ExtraEmptyLine']))->toBe(true);
                expect(isset($children['NoEmptyLine']))->toBe(true);

            });

            it("returns `null` on unknown child", function() {

                $children = $this->metrics->children('unknown_child');
                expect($children)->toBe([]);

            });

        });

    });

});
