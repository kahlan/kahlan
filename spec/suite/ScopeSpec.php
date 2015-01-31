<?php
namespace kahlan\spec\suite;

use Exception;
use kahlan\SkipException;
use kahlan\IncompleteException;
use kahlan\Arg;
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
                'clear',
                'context',
                'current',
                'describe',
                'exception',
                'exclusive',
                'expect',
                'fail',
                'failfast',
                'hash',
                'incomplete',
                'it',
                'log',
                'message',
                'messages',
                'pass',
                'passed',
                'process',
                'register',
                'registered',
                'report',
                'reset',
                'results',
                'run',
                'skip',
                'skipIf',
                'status',
                'xcontext',
                'xdescribe',
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

        context("when nested",
                function() {

            beforeEach(function() {
                $this->bar = 1;
            });

            it("can access variable from the parent scope", function() {

                expect($this->bar)->toBe(1);

            });
        });
    });

    describe("skipIf", function() {

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

            $this->scope->pass(['data' => 'value']);
            $results = $this->scope->results();
            $result = reset($results['passed']);

            expect($result['data'])->toBe('value');
            expect($result['type'])->toBe('pass');
            expect($result['messages'])->toBe(['it runs a spec']);
            expect($result['backtrace'])->toBeAn('array');

        });

    });

    describe("->fail()", function() {

        it("logs a fail", function() {

            $this->scope->fail(['data' => 'value']);
            $results = $this->scope->results();
            $result = reset($results['failed']);

            expect($result['data'])->toBe('value');
            expect($result['type'])->toBe('fail');
            expect($result['messages'])->toBe(['it runs a spec']);
            expect($result['backtrace'])->toBeAn('array');

        });

    });

    describe("->exception()", function() {

        it("logs a fail", function() {

            $this->scope->exception([
                'data' => 'value',
                'exception' => new Exception()
            ]);
            $results = $this->scope->results();
            $result = reset($results['exceptions']);

            expect($result['data'])->toBe('value');
            expect($result['type'])->toBe('exception');
            expect($result['messages'])->toBe(['it runs a spec']);
            expect($result['backtrace'])->toBeAn('array');

        });

    });

    describe("->skip()", function() {

        it("logs a skip", function() {

            $this->scope->skip([
                'data' => 'value',
                'exception' => new SkipException()
            ]);
            $results = $this->scope->results();
            $result = reset($results['skipped']);

            expect($result['data'])->toBe('value');
            expect($result['type'])->toBe('skip');
            expect($result['messages'])->toBe(['it runs a spec']);
            expect($result['backtrace'])->toBeAn('array');

        });

    });

    describe("->incomplete()", function() {

        it("logs a fail", function() {

            $this->scope->incomplete([
                'data' => 'value',
                'exception' => new IncompleteException()
            ]);
            $results = $this->scope->results();
            $result = reset($results['incomplete']);

            expect($result['data'])->toBe('value');
            expect($result['type'])->toBe('incomplete');
            expect($result['messages'])->toBe(['it runs a spec']);
            expect($result['backtrace'])->toBeAn('array');

        });

    });

});
