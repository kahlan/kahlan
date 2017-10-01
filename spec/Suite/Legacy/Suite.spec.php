<?php
namespace Kahlan\Spec\Suite\Legacy;

use stdClass;
use Exception;
use InvalidArgumentException;

use Kahlan\MissingImplementationException;
use Kahlan\PhpErrorException;
use Kahlan\Suite;
use Kahlan\Matcher;
use Kahlan\Arg;
use Kahlan\Plugin\Double;

describe("Legacy", function () {

    describe("Suite", function () {

        beforeAll(function () {
            Suite::$PHP = 5;
        });

        afterAll(function () {
            Suite::$PHP = PHP_MAJOR_VERSION;
        });

        beforeEach(function () {
            $this->suite = new Suite(['matcher' => new Matcher()]);
            $this->root = $this->suite->root();
        });

        describe("->run()", function () {

            it("run the suite", function () {

                $describe = $this->root->describe("", function () {

                    $this->it("runs a spec", function () {
                        $this->expect(true)->toBe(true);
                    });

                });

                $this->suite->run();
                expect($this->suite->status())->toBe(0);

            });

            it("calls `afterX` callbacks if an exception occurs during callbacks", function () {

                $describe = $this->root->describe("", function () {

                    $this->inAfterEach = 0;

                    $this->beforeEach(function () {
                        throw new Exception('Breaking the flow should execute afterEach anyway.');
                    });

                    $this->it("does nothing", function () {
                    });

                    $this->afterEach(function () {
                        $this->inAfterEach++;
                    });

                });

                $this->suite->run();

                expect($describe->scope()->inAfterEach)->toBe(1);

                $results = $this->suite->summary()->logs('errored');
                expect($results)->toHaveLength(1);

                $report = reset($results);
                $actual = $report->exception()->getMessage();
                expect($actual)->toBe('Breaking the flow should execute afterEach anyway.');

                expect($this->suite->status())->toBe(-1);

            });

            it("logs `MissingImplementationException` when thrown", function () {

                $missing = new MissingImplementationException();

                $describe = $this->root->describe("", function () use ($missing) {

                    $this->it("throws an `MissingImplementationException`", function () use ($missing) {
                        throw $missing;
                    });

                });

                $this->suite->run();

                $results = $this->suite->summary()->logs('errored');
                expect($results)->toHaveLength(1);

                $report = reset($results);
                expect($report->exception())->toBe($missing);
                expect($report->type())->toBe('errored');
                expect($report->messages())->toBe(['', '', 'it throws an `MissingImplementationException`']);

                expect($this->suite->status())->toBe(-1);

            });

            it("fails fast", function () {

                $describe = $this->root->describe("", function () {

                    $this->it("fails1", function () {
                        $this->expect(true)->toBe(false);
                    });

                    $this->it("fails2", function () {
                        $this->expect(true)->toBe(false);
                    });

                    $this->it("fails3", function () {
                        $this->expect(true)->toBe(false);
                    });

                });

                $this->suite->run(['ff' => 1]);

                $failed = $this->suite->summary()->logs('failed');

                expect($failed)->toHaveLength(1);
                expect($this->root->focused())->toBe(false);
                expect($this->suite->status())->toBe(-1);

            });

            it("fails after two failures", function () {

                $describe = $this->root->describe("", function () {

                    $this->it("fails1", function () {
                        $this->expect(true)->toBe(false);
                    });

                    $this->it("fails2", function () {
                        $this->expect(true)->toBe(false);
                    });

                    $this->it("fails3", function () {
                        $this->expect(true)->toBe(false);
                    });

                });

                $this->suite->run(['ff' => 2]);

                $failed = $this->suite->summary()->logs('failed');

                expect($failed)->toHaveLength(2);
                expect($this->root->focused())->toBe(false);
                expect($this->suite->status())->toBe(-1);

            });

        });

        describe("skipIf", function () {

            it("skips specs in a before", function () {

                $describe = $this->root->describe("skip suite", function () {

                    $this->exectuted = ['it' => 0];

                    beforeAll(function () {
                        skipIf(true);
                    });

                    $this->it("an it", function () {
                        $this->exectuted['it']++;
                    });

                    $this->it("an it", function () {
                        $this->exectuted['it']++;
                    });

                });
                $reporters = Double::instance();

                expect($reporters)->toReceive('dispatch')->with('start', ['total' => 2])->ordered;
                expect($reporters)->toReceive('dispatch')->with('suiteStart', $describe)->ordered;
                expect($reporters)->toReceive('dispatch')->with('specStart', Arg::toBeAnInstanceOf('Kahlan\Block\Specification'))->ordered;
                expect($reporters)->toReceive('dispatch')->with('specEnd', Arg::toBeAnInstanceOf('Kahlan\Log'))->ordered;
                expect($reporters)->toReceive('dispatch')->with('specStart', Arg::toBeAnInstanceOf('Kahlan\Block\Specification'))->ordered;
                expect($reporters)->toReceive('dispatch')->with('specEnd', Arg::toBeAnInstanceOf('Kahlan\Log'))->ordered;
                expect($reporters)->toReceive('dispatch')->with('suiteEnd', $describe)->ordered;
                expect($reporters)->toReceive('dispatch')->with('end', Arg::toBeAnInstanceOf('Kahlan\Summary'))->ordered;

                $this->suite->run(['reporters' => $reporters]);

                expect($describe->scope()->exectuted)->toEqual(['it' => 0]);
                expect($this->root->focused())->toBe(false);
                expect($this->suite->status())->toBe(0);

            });

            it("skips specs in a beforeEach", function () {

                $describe = $this->root->describe("skip suite", function () {

                    $this->exectuted = ['it' => 0];

                    beforeEach(function () {
                        skipIf(true);
                    });

                    $this->it("an it", function () {
                        $this->exectuted['it']++;
                    });

                    $this->it("an it", function () {
                        $this->exectuted['it']++;
                    });

                });

                $reporters = Double::instance();

                expect($reporters)->toReceive('dispatch')->with('start', ['total' => 2])->ordered;
                expect($reporters)->toReceive('dispatch')->with('suiteStart', $describe)->ordered;
                expect($reporters)->toReceive('dispatch')->with('specStart', Arg::toBeAnInstanceOf('Kahlan\Block\Specification'))->ordered;
                expect($reporters)->toReceive('dispatch')->with('specEnd', Arg::toBeAnInstanceOf('Kahlan\Log'))->ordered;
                expect($reporters)->toReceive('dispatch')->with('specStart', Arg::toBeAnInstanceOf('Kahlan\Block\Specification'))->ordered;
                expect($reporters)->toReceive('dispatch')->with('suiteEnd', $describe)->ordered;
                expect($reporters)->toReceive('dispatch')->with('end', Arg::toBeAnInstanceOf('Kahlan\Summary'))->ordered;

                $this->suite->run(['reporters' => $reporters]);

                expect($describe->scope()->exectuted)->toEqual(['it' => 0]);
                expect($this->root->focused())->toBe(false);
                expect($this->suite->status())->toBe(0);

            });

        });

    });

});
