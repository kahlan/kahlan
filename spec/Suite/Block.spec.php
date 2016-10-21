<?php
namespace Kahlan\Spec\Suite;

use Exception;
use Kahlan\SkipException;
use Kahlan\Suite;
use Kahlan\Summary;
use Kahlan\Log;
use Kahlan\Plugin\Double;

describe("Block", function () {

    beforeEach(function () {
        $this->block = $block = Double::classname(['extends' => 'Kahlan\Block']);

        $this->createScope= function ($options = []) use ($block) {
            return new $block($options);
        };
        $this->scope = $this->createScope(['message' => 'it runs a spec']);
    });

    describe("->__construct()", function () {

        it("sets passed options", function () {

            $log = new Log();

            $scope = $this->createScope([
                'type'    => 'focus',
                'message' => 'test',
                'parent'  => null,
                'root'    => null,
                'log'     => $log,
                'timeout' => 10
            ]);

            expect($scope->type())->toBe('focus');
            expect($scope->message())->toBe('test');
            expect($scope->parent())->toBe(null);
            expect($scope->log())->toBe($log);
            expect($scope->suite()->summary())->toBe($scope->summary());

        });

    });

    describe("->parent()", function () {

        it("returns the parent node", function () {

            $parent = $this->createScope();
            $this->scope = $this->createScope(['parent' => $parent]);
            expect($this->scope->parent())->toBe($parent);

        });

    });

    describe("->backtrace()", function () {

        it("returns the backtrace", function () {

            $this->scope = $this->createScope();
            expect(basename($this->scope->backtrace()[1]['file']))->toBe('Block.spec.php');

        });

    });

    describe("->__get/__set()", function () {

        it("defines a value in the current scope", function () {

            $this->foo = 2;
            expect($this->foo)->toEqual(2);

        });

        it("is not influenced by the previous spec", function () {

            expect(isset($this->foo))->toBe(false);

        });

        it("throw an new exception for reserved keywords", function () {

            foreach ([
                '__construct' => true,
                '__call'      => true,
                '__get'       => true,
                '__set'       => true,
                'given'       => true,
                'afterAll'    => true,
                'afterEach'   => true,
                'beforeAll'   => true,
                'beforeEach'  => true,
                'context'     => true,
                'describe'    => true,
                'expect'      => true,
                'given'       => true,
                'it'          => true,
                'fdescribe'   => true,
                'fcontext'    => true,
                'fit'         => true,
                'skipIf'      => true,
                'xdescribe'   => true,
                'xcontext'    => true,
                'xit'         => true
            ] as $keyword => $bool) {
                $closure = function () use ($keyword) {
                    $this->{$keyword} = 'some value';
                };
                expect($closure)->toThrow(new Exception("Sorry `{$keyword}` is a reserved keyword, it can't be used as a scope variable."));
            }

        });

        it("throws an exception on undefined variables", function () {

            $closure = function () {
                $a = $this->unexisting;
            };

            expect($closure)->toThrow(new Exception('Undefined variable `unexisting`.'));

        });

        context("when nested", function () {

            beforeEach(function () {
                $this->bar = 1;
            });

            it("can access variable from the parent scope", function () {

                expect($this->bar)->toBe(1);

            });
        });
    });

    describe("skipIf", function () {

        it("returns none if provided false/null", function () {

            expect(skipIf(false))->toBe(null);

        });

        $executed = 0;

        context("when used in a scope", function () use (&$executed) {

            beforeAll(function () {
                skipIf(true);
            });

            it("skips this spec", function () use (&$executed) {

                expect(true)->toBe(false);
                $executed++;

            });

            it("skips this spec too", function () use (&$executed) {

                expect(true)->toBe(false);
                $executed++;

            });

        });

        it("expects that no spec have been runned", function () use (&$executed) {

            expect($executed)->toBe(0);

        });

        context("when used in a spec", function () use (&$executed) {

            it("skips this spec", function () use (&$executed) {

                skipIf(true);
                expect(true)->toBe(false);
                $executed++;

            });

            it("doesn't skip this spec", function () use (&$executed) {

                $executed++;
                expect(true)->toBe(true);
            });

        });

        it("expects that only one test have been runned", function () use (&$executed) {

            expect($executed)->toBe(1);

        });

    });

    describe("__call", function () {

        $this->customMethod = function ($self) {
            $self->called = true;
            return 'called';
        };

        it("calls closure assigned to scope property to be inkovable", function () {

            $actual = $this->customMethod($this);
            expect($actual)->toBe('called');
            expect($this->called)->toBe(true);

        });

        it("throws an exception on no closure variable", function () {

            $closure = function () {
                $this->mystring = 'hello';
                $a = $this->mystring();
            };

            expect($closure)->toThrow(new Exception('Uncallable variable `mystring`.'));

        });

    });

    describe("->pass()", function () {

        it("logs a pass", function () {

            $this->scope->log('passed', ['matcher' => 'Kahlan\Matcher\ToBe']);
            $expectation = $this->scope->log()->children()[0];
            expect($expectation->matcher())->toBe('Kahlan\Matcher\ToBe');
            expect($expectation->type())->toBe('passed');
            expect($expectation->messages())->toBe(['it runs a spec']);

        });

    });

    describe("->fail()", function () {

        it("logs a fail", function () {

            $this->scope->log('failed', ['matcher' => 'Kahlan\Matcher\ToBe']);
            $expectation = $this->scope->log()->children()[0];
            expect($expectation->matcher())->toBe('Kahlan\Matcher\ToBe');
            expect($expectation->type())->toBe('failed');
            expect($expectation->messages())->toBe(['it runs a spec']);

        });

    });

    describe("->timeout()", function () {

        it("gets/sets the timeout value", function () {

            $this->scope->timeout(5);
            expect($this->scope->timeout())->toBe(5);

            $this->scope->timeout(null);
            expect($this->scope->timeout())->toBe(null);

        });

    });

});
