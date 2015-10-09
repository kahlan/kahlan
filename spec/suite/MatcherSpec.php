<?php
namespace kahlan\spec\suite;

use Exception;
use stdClass;
use DateTime;
use kahlan\Spec;
use kahlan\Matcher;
use kahlan\plugin\Stub;

describe("Matcher", function() {

    beforeEach(function() {

        $this->spec = new Spec([
            'message' => 'runs a spec',
            'closure' => function() {}
        ]);

        $this->matchers = Matcher::get();

    });

    afterEach(function() {

        foreach ($this->matchers as $name => $value) {
            foreach ($value as $for => $class) {
                Matcher::register($name, $class, $for);
            }
        }

    });

    describe("->__call()", function() {

        context("when the matcher passes", function() {

            it("logs a pass", function() {

                $matcher = new Matcher();
                $result = $matcher->expect(true, $this->spec)->toBe(true);
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

                $matcher = new Matcher();
                $stub = Stub::create();
                $result = $matcher->expect($stub, $this->spec)->toReceive('methodName');
                $stub->methodName();
                $matcher->resolve();

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

                $matcher = new Matcher();
                $result = $matcher->expect(true, $this->spec)->not->toBe(false);
                expect($this->spec->passed())->toBe(true);

                $passes = $this->spec->results()['passed'];
                expect($passes)->toHaveLength(1);

                $pass = reset($passes);

                expect($pass->not())->toBe(true);

            });

            it("logs the not attribute with a deferred matcher", function() {

                $matcher = new Matcher();
                $stub = Stub::create();
                $result = $matcher->expect($stub, $this->spec)->not->toReceive('methodName');
                $matcher->resolve();

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

            it("doesn't wait when the spec passes", function () {

                $start = microtime(true);
                $matcher = new Matcher();
                $result = $matcher->expect(true, $this->spec, 1000000)->toBe(true); // 1s
                expect($this->spec->passed())->toBe(true);
                $end = microtime(true);
                expect($end - $start)->toBeLessThan(1);

            });

            it("loops until the timeout is reached on failure", function () {

                $start = microtime(true);
                $matcher = new Matcher();
                $result = $matcher->expect(true, $this->spec, 100000)->toBe(false); // 0.1s
                expect($this->spec->passed())->toBe(false);
                $end = microtime(true);
                expect($end - $start)->toBeGreaterThan(0.1);
                expect($end - $start)->toBeLessThan(0.2);

            });

        });

        context("when the matcher fails", function() {

            it("logs a fail", function() {

                $matcher = new Matcher();
                $result = $matcher->expect(true, $this->spec)->toBe(false);
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

                $matcher = new Matcher();
                $stub = Stub::create();
                $result = $matcher->expect($stub, $this->spec)->toReceive('methodName');
                $matcher->resolve();

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

                $matcher = new Matcher();
                $result = $matcher->expect(true, $this->spec)->not->toBe(true);
                expect($this->spec->passed())->toBe(false);

                $failures = $this->spec->results()['failed'];
                expect($failures)->toHaveLength(1);

                $failure = reset($failures);

                expect($failure->not())->toBe(true);

            });

            it("logs the not attribute with a deferred matcher", function() {

                $matcher = new Matcher();
                $stub = Stub::create();
                $result = $matcher->expect($stub, $this->spec)->not->toReceive('methodName');
                $stub->methodName();
                $matcher->resolve();

                expect($this->spec->passed())->toBe(false);

                $failures = $this->spec->results()['failed'];
                expect($failures)->toHaveLength(1);

                $failure = reset($failures);

                expect($failure->not())->toBe(true);

            });

        });

        it("throws an exception when using an undefined matcher name", function() {

            $closure = function() {
                $matcher = new Matcher();
                $result = $matcher->expect(true, $this->spec)->toHelloWorld(true);
            };

            expect($closure)->toThrow(new Exception('Error, undefined matcher `toHelloWorld`.'));

        });

        it("throws an exception when a specific class matcher doesn't match", function() {

            Matcher::register('toEqualCustom', Stub::classname(['extends' => 'kahlan\matcher\ToEqual']), 'stdClass');

            $closure = function() {
                $matcher = new Matcher();
                $result = $matcher->expect([], $this->spec)->toEqualCustom(new stdClass());
            };

            expect($closure)->toThrow(new Exception('Error, undefined matcher `toEqualCustom`.'));

        });

    });

    describe("::register()", function() {

        it("registers a matcher", function() {

            Matcher::register('toBeOrNotToBe', Stub::classname(['extends' => 'kahlan\matcher\ToBe']));
            expect(Matcher::exists('toBeOrNotToBe'))->toBe(true);
            expect(Matcher::exists('toBeOrNot'))->toBe(false);

            expect(true)->toBeOrNotToBe(true);

        });

        it("registers a matcher for a specific class", function() {

            Matcher::register('toEqualCustom', Stub::classname(['extends' => 'kahlan\matcher\ToEqual']), 'stdClass');
            expect(Matcher::exists('toEqualCustom', 'stdClass'))->toBe(true);
            expect(Matcher::exists('toEqualCustom'))->toBe(false);

            expect(new stdClass())->toEqualCustom(new stdClass());
            expect(new stdClass())->not->toEqualCustom(new DateTime());

        });

    });

    describe("::get()", function() {

        it("returns all registered matchers", function() {

            Matcher::reset();
            Matcher::register('toBe', 'kahlan\matcher\ToBe');

            expect(Matcher::get())->toBe([
                'toBe' => ['' => 'kahlan\matcher\ToBe']
            ]);

        });

        it("returns a registered matcher", function() {

            expect(Matcher::get('toBe'))->toBe('kahlan\matcher\ToBe');

        });

        it("returns the default registered matcher", function() {

            expect(Matcher::get('toBe', 'stdClass'))->toBe('kahlan\matcher\ToBe');

        });

        it("returns a custom matcher when defined for a specific class", function() {

            Matcher::register('toBe', 'kahlan\custom\ToBe', 'stdClass');

            expect(Matcher::get('toBe', 'DateTime'))->toBe('kahlan\matcher\ToBe');
            expect(Matcher::get('toBe', 'stdClass'))->toBe('kahlan\custom\ToBe');

        });

    });

    describe("::unregister()", function() {

        it("unregisters a matcher", function() {

            Matcher::register('toBeOrNotToBe', Stub::classname(['extends' => 'kahlan\matcher\ToBe']));
            expect(Matcher::exists('toBeOrNotToBe'))->toBe(true);

            Matcher::unregister('toBeOrNotToBe');
            expect(Matcher::exists('toBeOrNotToBe'))->toBe(false);

        });

        it("unregisters all matchers", function() {

            expect(Matcher::get())->toBeGreaterThan(1);
            Matcher::unregister(true);
            Matcher::register('toHaveLength', 'kahlan\matcher\ToHaveLength');
            expect(Matcher::get())->toHaveLength(1);

        });

    });

    describe("::reset()", function() {

         it("unregisters all matchers", function() {

            expect(Matcher::get())->toBeGreaterThan(1);
            Matcher::reset();
            Matcher::register('toHaveLength', 'kahlan\matcher\ToHaveLength');
            expect(Matcher::get())->toHaveLength(1);

        });

    });

});