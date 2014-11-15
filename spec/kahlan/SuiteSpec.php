<?php
namespace spec\kahlan;

use kahlan\PhpErrorException;
use kahlan\Suite;
use kahlan\Matcher;

describe("Suite", function() {

    beforeEach(function() {
        $this->suite = new Suite(['matcher' => new Matcher()]);
    });

    describe("->before()", function() {

        $this->nb = 0;

        before(function() {
            $this->nb++;
        });

        it("passes if `before` has been executed", function() use (&$nb) {

            expect($this->nb)->toBe(1);

        });

        it("passes if `before` has not been executed twice", function() use (&$nb) {

            expect($this->nb)->toBe(1);

        });

    });

    describe("->beforeEach()", function() {

        $this->nb = 0;

        beforeEach(function() {
            $this->nb++;
        });

        it("passes if `beforeEach` has been executed", function() {

            expect($this->nb)->toBe(1);

        });

        it("passes if `beforeEach` has been executed twice", function() {

            expect($this->nb)->toBe(2);

        });

        context("with sub scope", function() {

            it("passes if `beforeEach` has been executed once more", function() {

                expect($this->nb)->toBe(3);

            });

        });

        it("passes if `beforeEach` has been executed once more", function() {

            expect($this->nb)->toBe(4);

        });

    });

    describe("->after()", function() {

        $this->nb = 0;

        after(function() {
            $this->nb++;
        });

        it("passes if `after` has not been executed", function() {

            expect($this->nb)->toBe(0);

        });

    });

    describe("->afterEach()", function() {

        $this->nb = 0;

        afterEach(function() {
            $this->nb++;
        });

        it("passes if `afterEach` has not been executed", function() {

            expect($this->nb)->toBe(0);

        });

        it("passes if `afterEach` has been executed", function() {

            expect($this->nb)->toBe(1);

        });

        context("with sub scope", function() {

            it("passes if `afterEach` has been executed once more", function() {

                expect($this->nb)->toBe(2);

            });

        });

        it("passes if `afterEach` has been executed once more", function() {

            expect($this->nb)->toBe(3);

        });

    });

    describe("->xdescribe()", function() {

        it("executes only the exclusive `it`", function() {

            $describe = $this->suite->describe("", function() {

                $this->exectuted = ['it' => 0, 'xit' => 0];

                $this->xdescribe("->xdescribe()", function() {

                    $this->it("xdescribe it", function() {
                        $this->exectuted['xit']++;
                    });

                    $this->it("xdescribe it", function() {
                        $this->exectuted['xit']++;
                    });

                });

                $this->describe("->describe()", function() {

                    $this->it("describe it", function() {
                        $this->exectuted['it']++;
                    });

                    $this->it("describe it", function() {
                        $this->exectuted['it']++;
                    });

                });

            });

            $this->suite->run();

            expect($describe->exectuted)->toEqual(['it' => 0, 'xit' => 2]);
            expect($this->suite->exclusive())->toBe(true);
            expect($this->suite->status())->toBe(-1);
            expect($this->suite->passed())->toBe(true);

        });

    });

    describe("->xcontext()", function() {

        it("executes only the exclusive `it`", function() {

            $describe = $this->suite->describe("", function() {

                $this->exectuted = ['it' => 0, 'xit' => 0];

                $this->xcontext("xcontext", function() {

                    $this->it("xcontext it", function() {
                        $this->exectuted['xit']++;
                    });

                    $this->it("xcontext it", function() {
                        $this->exectuted['xit']++;
                    });

                });

                $this->context("context", function() {

                    $this->it("context it", function() {
                        $this->exectuted['it']++;
                    });

                    $this->it("context it", function() {
                        $this->exectuted['it']++;
                    });

                });

            });

            $this->suite->run();

            expect($describe->exectuted)->toEqual(['it' => 0, 'xit' => 2]);
            expect($this->suite->exclusive())->toBe(true);
            expect($this->suite->status())->toBe(-1);
            expect($this->suite->passed())->toBe(true);

        });

    });

    describe("->xit()", function() {

        it("executes only the exclusive `it`", function() {

            $describe = $this->suite->describe("", function() {

                $this->exectuted = ['it' => 0, 'xit' => 0];

                $this->it("an it", function() {
                    $this->exectuted['it']++;
                });

                $this->xit("an xit", function() {
                    $this->exectuted['xit']++;
                });

                $this->it("an it", function() {
                    $this->exectuted['it']++;
                });

                $this->xit("an xit", function() {
                    $this->exectuted['xit']++;
                });

            });

            $this->suite->run();

            expect($describe->exectuted)->toEqual(['it' => 0, 'xit' => 2]);
            expect($this->suite->exclusive())->toBe(true);
            expect($this->suite->status())->toBe(-1);
            expect($this->suite->passed())->toBe(true);

        });

    });


    describe("->_errorHandler()", function() {

        it("converts E_NOTICE error to an exception", function() {

            $closure = function() {
                $a = $b;
            };
            expect($closure)->toThrow(new PhpErrorException("`E_NOTICE` Undefined variable: b"));

        });

        it("converts E_WARNING error to an exception", function() {

            $closure = function() {
                $a = array_merge();
            };
            expect($closure)->toThrow(new PhpErrorException("`E_WARNING` array_merge() expects at least 1 parameter, 0 given"));

        });

    });

});
