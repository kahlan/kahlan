<?php
namespace kahlan\spec\suite;

use Exception;
use kahlan\Specification;
use kahlan\Matcher;
use kahlan\plugin\Stub;

describe("Specification", function() {

    beforeEach(function() {

        $this->spec = new Specification(['closure' => function() {}]);

    });

    describe("->__construct()", function() {

        it("throws an exception with invalid closure", function() {

            $closure = function() {
                $this->spec = new Specification(['closure' => null]);
            };

            expect($closure)->toThrow(new Exception('Error, invalid closure.'));

        });

    });

    describe("->expect()", function() {

        it("returns the matcher instance", function() {

            $matcher = $this->spec->expect('actual');
            expect($matcher)->toBeAnInstanceOf('kahlan\Expectation');

        });

    });

    describe("->waitsFor()", function() {

        it("returns the matcher instance setted with the correct timeout", function() {

            $matcher = $this->spec->waitsFor(function(){}, 10);
            expect($matcher)->toBeAnInstanceOf('kahlan\Expectation');
            expect($matcher->timeout())->toBe(10);

            $matcher = $this->spec->waitsFor(function(){});
            expect($matcher)->toBeAnInstanceOf('kahlan\Expectation');
            expect($matcher->timeout())->toBe(0);

        });

    });

    describe("->process()", function() {

        it("returns the closure return value", function() {

            $this->spec = new Specification([
                'closure' => function() {
                    return 'hello world';
                }
            ]);

            expect($this->spec->process())->toBe('hello world');

        });

        context("when the specs passed", function() {

            it("logs a pass", function() {

                $this->spec = new Specification([
                    'message' => 'runs a spec',
                    'closure' => function() {
                        $this->expect(true)->toBe(true);
                    }
                ]);

                expect($this->spec->process())->toBe(null);
                expect($this->spec->passed())->toBe(true);

                $passed = $this->spec->results()['passed'];
                expect($passed)->toHaveLength(1);

                $pass = reset($passed);

                expect($pass->matcher())->toBe('kahlan\matcher\ToBe');
                expect($pass->matcherName())->toBe('toBe');
                expect($pass->not())->toBe(false);
                expect($pass->type())->toBe('pass');
                expect($pass->params())->toBe([
                    'actual'   => true,
                    'expected' => true
                ]);
                expect($pass->messages())->toBe(['it runs a spec']);

            });

            it("logs a pass with a deferred matcher", function() {

                $this->spec = new Specification([
                    'message' => 'runs a spec',
                    'closure' => function() {
                        $stub = Stub::create();
                        $this->expect($stub)->toReceive('methodName');
                        $stub->methodName();
                    }
                ]);

                expect($this->spec->process())->toBe(null);
                expect($this->spec->passed())->toBe(true);

                $passes = $this->spec->results()['passed'];
                expect($passes)->toHaveLength(1);

                $pass = reset($passes);

                expect($pass->matcher())->toBe('kahlan\matcher\ToReceive');
                expect($pass->matcherName())->toBe('toReceive');
                expect($pass->not())->toBe(false);
                expect($pass->type())->toBe('pass');
                expect($pass->params())->toBe([
                    'actual with'   => [],
                    'expected with' => []
                ]);
                expect($pass->messages())->toBe(['it runs a spec']);

            });

            it("logs the not attribute", function() {

                $this->spec = new Specification([
                    'closure' => function() {
                        $this->expect(true)->not->toBe(false);
                    }
                ]);

                expect($this->spec->process())->toBe(null);
                expect($this->spec->passed())->toBe(true);

                $passes = $this->spec->results()['passed'];
                expect($passes)->toHaveLength(1);

                $pass = reset($passes);

                expect($pass->not())->toBe(true);

            });

            it("logs the not attribute with a deferred matcher", function() {

                $this->spec = new Specification([
                    'closure' => function() {
                        $stub = Stub::create();
                        $this->expect($stub)->not->toReceive('methodName');
                    }
                ]);

                expect($this->spec->process())->toBe(null);
                expect($this->spec->passed())->toBe(true);

                $passes = $this->spec->results()['passed'];
                expect($passes)->toHaveLength(1);

                $pass = reset($passes);

                expect($pass->not())->toBe(true);

            });

            it("resets `not` to `false ` after any matcher call", function () {

                expect([])
                    ->not->toBeNull()
                    ->toBeA('array')
                    ->toBeEmpty();

            });

        });

        context("when the specs failed", function() {

            it("logs a fail", function() {

                $this->spec = new Specification([
                    'message' => 'runs a spec',
                    'closure' => function() {
                        $this->expect(true)->toBe(false);
                    }
                ]);

                expect($this->spec->process())->toBe(null);
                expect($this->spec->passed())->toBe(false);

                $failured = $this->spec->results()['failed'];
                expect($failured)->toHaveLength(1);

                $failure = reset($failured);

                expect($failure->matcher())->toBe('kahlan\matcher\ToBe');
                expect($failure->matcherName())->toBe('toBe');
                expect($failure->not())->toBe(false);
                expect($failure->type())->toBe('fail');
                expect($failure->params())->toBe([
                    'actual'   => true,
                    'expected' => false
                ]);
                expect($failure->messages())->toBe(['it runs a spec']);
                expect($failure->backtrace())->toBeAn('array');
            });

            it("logs a fail with a deferred matcher", function() {

                $this->spec = new Specification([
                    'message' => 'runs a spec',
                    'closure' => function() {
                        $stub = Stub::create();
                        $this->expect($stub)->toReceive('methodName');
                    }
                ]);

                expect($this->spec->process())->toBe(null);
                expect($this->spec->passed())->toBe(false);

                $failured = $this->spec->results()['failed'];
                expect($failured)->toHaveLength(1);

                $failure = reset($failured);

                expect($failure->matcher())->toBe('kahlan\matcher\ToReceive');
                expect($failure->matcherName())->toBe('toReceive');
                expect($failure->not())->toBe(false);
                expect($failure->type())->toBe('fail');
                expect($failure->params())->toBe([
                    'actual received' =>['__construct'],
                    'expected' => 'methodName'
                ]);
                expect($failure->description())->toBe('receive the correct message.');
                expect($failure->messages())->toBe(['it runs a spec']);
                expect($failure->backtrace())->toBeAn('array');

            });

            it("logs the not attribute", function() {

                $this->spec = new Specification([
                    'closure' => function() {
                        $this->expect(true)->not->toBe(true);
                    }
                ]);

                expect($this->spec->process())->toBe(null);
                expect($this->spec->passed())->toBe(false);

                $failures = $this->spec->results()['failed'];
                expect($failures)->toHaveLength(1);

                $failure = reset($failures);

                expect($failure->not())->toBe(true);

            });

            it("logs the not attribute with a deferred matcher", function() {

                $this->spec = new Specification([
                    'closure' => function() {
                        $stub = Stub::create();
                        $this->expect($stub)->not->toReceive('methodName');
                        $stub->methodName();
                    }
                ]);

                expect($this->spec->process())->toBe(null);
                expect($this->spec->passed())->toBe(false);

                $failures = $this->spec->results()['failed'];
                expect($failures)->toHaveLength(1);

                $failure = reset($failures);

                expect($failure->not())->toBe(true);
                expect($failure->not())->toBe(true);

            });

            it("logs sub spec fails", function() {

                $this->spec = new Specification([
                    'message' => 'runs a spec',
                    'closure' => function() {
                        $this->waitsFor(function(){
                            $this->expect(true)->toBe(false);
                        });
                    }
                ]);

                expect($this->spec->process())->toBe(null);
                expect($this->spec->passed())->toBe(false);

                $failured = $this->spec->results()['failed'];
                expect($failured)->toHaveLength(1);

                $failure = reset($failured);

                expect($failure->matcher())->toBe('kahlan\matcher\ToBe');
                expect($failure->matcherName())->toBe('toBe');
                expect($failure->not())->toBe(false);
                expect($failure->type())->toBe('fail');
                expect($failure->params())->toBe([
                    'actual'   => true,
                    'expected' => false
                ]);
                expect($failure->messages())->toBe(['it runs a spec']);
                expect($failure->backtrace())->toBeAn('array');
            });

            it("logs the first failing spec only", function() {

                $this->spec = new Specification([
                    'message' => 'runs a spec',
                    'closure' => function() {
                        $this->waitsFor(function(){
                            $this->expect(true)->toBe(false);
                            return true;
                        })->toBe(false);
                    }
                ]);

                expect($this->spec->process())->toBe(null);
                expect($this->spec->passed())->toBe(false);

                $failured = $this->spec->results()['failed'];
                expect($failured)->toHaveLength(1);

                $failure = reset($failured);

                expect($failure->matcher())->toBe('kahlan\matcher\ToBe');
                expect($failure->matcherName())->toBe('toBe');
                expect($failure->not())->toBe(false);
                expect($failure->type())->toBe('fail');
                expect($failure->params())->toBe([
                    'actual'   => true,
                    'expected' => false
                ]);
                expect($failure->messages())->toBe(['it runs a spec']);
                expect($failure->backtrace())->toBeAn('array');
            });

        });

    });

});