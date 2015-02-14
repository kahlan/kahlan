<?php
namespace kahlan\spec\suite;

use Exception;
use kahlan\Spec;
use kahlan\Matcher;
use kahlan\plugin\Stub;

describe("Matcher", function() {

    beforeEach(function() {

        $this->spec = new Spec([
            'message' => 'runs a spec',
            'closure' => function() {}
        ]);

    });

    afterEach(function() {

        Matcher::register('toBe', 'kahlan\matcher\ToBe');
        Matcher::register('toBeA', 'kahlan\matcher\ToBeA');
        Matcher::register('toBeAn', 'kahlan\matcher\ToBeA');
        Matcher::register('toBeAnInstanceOf', 'kahlan\matcher\ToBeAnInstanceOf');
        Matcher::register('toBeCloseTo', 'kahlan\matcher\ToBeCloseTo');
        Matcher::register('toBeEmpty', 'kahlan\matcher\ToBeFalsy');
        Matcher::register('toBeFalsy', 'kahlan\matcher\ToBeFalsy');
        Matcher::register('toBeGreaterThan', 'kahlan\matcher\ToBeGreaterThan');
        Matcher::register('toBeLessThan', 'kahlan\matcher\ToBeLessThan');
        Matcher::register('toBeNull', 'kahlan\matcher\ToBeNull');
        Matcher::register('toBeTruthy', 'kahlan\matcher\ToBeTruthy');
        Matcher::register('toContain', 'kahlan\matcher\ToContain');
        Matcher::register('toEcho', 'kahlan\matcher\ToEcho');
        Matcher::register('toEqual', 'kahlan\matcher\ToEqual');
        Matcher::register('toHaveLength', 'kahlan\matcher\ToHaveLength');
        Matcher::register('toMatch', 'kahlan\matcher\ToMatch');
        Matcher::register('toReceive', 'kahlan\matcher\ToReceive');
        Matcher::register('toReceiveNext', 'kahlan\matcher\ToReceiveNext');
        Matcher::register('toThrow', 'kahlan\matcher\ToThrow');

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

        it("throws an exception using an undefined matcher name", function() {

            $closure = function() {
                $matcher = new Matcher();
                $result = $matcher->expect(true, $this->spec)->toHelloWorld(true);
            };

            expect($closure)->toThrow(new Exception('Error, undefined matcher `toHelloWorld`.'));

        });

    });

    describe("::register()", function() {

        it("registers a matcher", function() {

            Matcher::register('toBeOrNotToBe', Stub::classname(['extends' => 'kahlan\matcher\ToBe']));
            expect(Matcher::exists('toBeOrNotToBe'))->toBe(true);
            expect(Matcher::exists('toBeOrNot'))->toBe(false);

        });

    });

    describe("::get()", function() {

        it("returns all registered matchers", function() {

            Matcher::reset();
            Matcher::register('toBe', 'kahlan\matcher\ToBe');

            expect(Matcher::get())->toBe([
                'toBe' => 'kahlan\matcher\ToBe'
            ]);

        });

        it("returns a registered matcher", function() {

            expect(Matcher::get('toBe'))->toBe('kahlan\matcher\ToBe');

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