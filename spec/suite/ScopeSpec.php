<?php
namespace kahlan\spec\suite;

use Exception;
use kahlan\SkipException;
use kahlan\IncompleteException;
use kahlan\Scope;

describe("Scope", function() {

    beforeEach(function() {

        $this->scope = new Scope(['message' => 'it runs a spec']);

    });

    describe("->__get/__set()", function() {

        it("defines a value in the current scope", function() {

            $this->foo = 2;
            expect($this->foo)->toEqual(2);

        });

        it("is not influenced by the previous spec", function() {

            expect(isset($this->foo))->toBe(false);

        });

        it("throw an new exception for reserved keywords", function() {

            $reserved = [
                '__construct',
                '__call',
                '__get',
                '__set',
                'after',
                'afterEach',
                'before',
                'beforeEach',
                'context',
                'current',
                'describe',
                'dispatch',
                'emitReport',
                'focus',
                'focused',
                'expect',
                'failfast',
                'hash',
                'it',
                'logs',
                'matcher',
                'message',
                'messages',
                'passed',
                'process',
                'register',
                'registered',
                'report',
                'reset',
                'results',
                'run',
                'skipIf',
                'status',
                'timeout',
                'wait',
                'fdescribe',
                'fcontext',
                'fit',
                'xdescribe',
                'xcontext',
                'xit'
            ];

            foreach ($reserved as $keyword) {
                $closure = function() use ($keyword) {
                    $this->{$keyword} = 'some value';
                };
                expect($closure)->toThrow(new Exception("Sorry `{$keyword}` is a reserved keyword, it can't be used as a scope variable."));
            }

        });

        it("throws an exception on undefined variables", function() {

            $closure = function() {
                $a = $this->unexisting;
            };

            expect($closure)->toThrow(new Exception('Undefined variable `unexisting`.'));

        });

        it("throws properly message on expect() usage inside of describe()", function() {

            $closure = function() {
                $this->expect;
            };


            expect($closure)->toThrow(new Exception("You can't use expect() inside of describe()"));

        });

        context("when nested", function() {

            beforeEach(function() {
                $this->bar = 1;
            });

            it("can access variable from the parent scope", function() {

                expect($this->bar)->toBe(1);

            });
        });
    });

    describe("skipIf", function() {

        it("returns none if provided false/null", function() {

            expect(skipIf(false))->toBe(null);

        });

        $executed = 0;

        context("when used in a scope", function() use (&$executed) {

            before(function() {
                skipIf(true);
            });

            it("skips this spec", function() use (&$executed) {

                expect(true)->toBe(false);
                $executed++;

            });

            it("skips this spec too", function() use (&$executed) {

                expect(true)->toBe(false);
                $executed++;

            });

        });

        it("expects that no spec have been runned", function() use (&$executed) {

            expect($executed)->toBe(0);

        });

        context("when used in a spec", function() use (&$executed) {

            it("skips this spec", function() use (&$executed) {

                skipIf(true);
                expect(true)->toBe(false);
                $executed++;

            });

            it("doesn't skip this spec", function() use (&$executed) {

                $executed++;

            });

        });

        it("expects that only one test have been runned", function() use (&$executed) {

            expect($executed)->toBe(1);

        });

    });

    describe("__call", function() {

        $this->customMethod = function($self) {
            $self->called = true;
            return 'called';
        };

        it("calls closure assigned to scope property to be inkovable", function() {

            $actual = $this->customMethod($this);
            expect($actual)->toBe('called');
            expect($this->called)->toBe(true);

        });

        it("throws an exception on no closure variable", function() {

            $closure = function() {
                $this->mystring = 'hello';
                $a = $this->mystring();
            };

            expect($closure)->toThrow(new Exception('Uncallable variable `mystring`.'));

        });

    });

    describe("->pass()", function() {

        it("logs a pass", function() {

            $this->scope->report()->add('pass', ['matcher' => 'kahlan\matcher\ToBe']);
            $results = $this->scope->results();
            $report = reset($results['passed']);

            expect($report->matcher())->toBe('kahlan\matcher\ToBe');
            expect($report->type())->toBe('pass');
            expect($report->messages())->toBe(['it runs a spec']);
            expect($report->backtrace())->toBeAn('array');

        });

    });

    describe("->fail()", function() {

        it("logs a fail", function() {

            $this->scope->report()->add('fail', ['matcher' => 'kahlan\matcher\ToBe']);
            $results = $this->scope->results();
            $report = reset($results['failed']);

            expect($report->matcher())->toBe('kahlan\matcher\ToBe');
            expect($report->type())->toBe('fail');
            expect($report->messages())->toBe(['it runs a spec']);
            expect($report->backtrace())->toBeAn('array');

        });

    });

    describe("->exception()", function() {

        it("logs a fail", function() {

            $this->scope->report()->add('exception', [
                'matcher' => 'kahlan\matcher\toThrow',
                'exception' => new Exception()
            ]);
            $results = $this->scope->results();
            $report = reset($results['exceptions']);

            expect($report->matcher())->toBe('kahlan\matcher\toThrow');
            expect($report->type())->toBe('exception');
            expect($report->messages())->toBe(['it runs a spec']);
            expect($report->backtrace())->toBeAn('array');

        });

    });

    describe("->skip()", function() {

        it("logs a skip", function() {

            $this->scope->report()->add('skip', [
                'exception' => new SkipException()
            ]);
            $results = $this->scope->results();
            $report = reset($results['skipped']);

            expect($report->type())->toBe('skip');
            expect($report->messages())->toBe(['it runs a spec']);
            expect($report->backtrace())->toBeAn('array');
        });

    });

    describe("->incomplete()", function() {

        it("logs a fail", function() {

            $this->scope->report()->add('incomplete', [
                'exception' => new IncompleteException()
            ]);
            $results = $this->scope->results();
            $report = reset($results['incomplete']);

            expect($report->type())->toBe('incomplete');
            expect($report->messages())->toBe(['it runs a spec']);
            expect($report->backtrace())->toBeAn('array');

        });

    });

    describe("->timeout()", function() {

        it("gets/sets the timeout value", function() {

            $this->scope->timeout(5);
            expect($this->scope->timeout())->toBe(5);

            $this->scope->timeout(null);
            expect($this->scope->timeout())->toBe(null);

        });

    });

});
