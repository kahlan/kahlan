<?php
namespace spec\kahlan;

use stdClass;
use Exception;
use InvalidArgumentException;

use kahlan\PhpErrorException;
use kahlan\Suite;
use kahlan\Matcher;

describe("Suite", function() {

    beforeEach(function() {
        $this->suite = new Suite(['matcher' => new Matcher()]);
    });

    context("when inspecting flow", function() {

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

    });

    describe("->describe()", function() {

        it("creates a sub suite of specs inside the root suite", function() {

            $suite = $this->suite->describe("->method()", function() {});

            expect($suite->message())->toBe('->method()');
            expect($suite->parent())->toBe($this->suite);

            $suites = $this->suite->childs();
            expect($suite)->toBe(end($suites));

        });

    });

    describe("->context()", function() {

        it("creates a contextualized suite of specs inside the root suite", function() {

            $suite = $this->suite->context("->method()", function() {});

            expect($suite->message())->toBe('->method()');
            expect($suite->parent())->toBe($this->suite);

            $suites = $this->suite->childs();
            expect($suite)->toBe(end($suites));

        });

    });

    describe("->it()", function() {

        it("creates a spec", function() {

            $this->suite->it("does some things", function() {});

            $specs = $this->suite->childs();
            $it = end($specs);

            expect($it->message())->toBe('it does some things');
            expect($it->parent())->toBe($this->suite);

        });

        it("creates a spec with a random message if not set", function() {

            $this->suite->it(function() {});

            $specs = $this->suite->childs();
            $it = end($specs);

            expect($it->message())->toMatch('~^it spec #[0-9]+$~');

        });

    });

    describe("->before()", function() {

        it("creates a before callback", function() {

            $callbacks = $this->suite->callbacks('before');
            expect($callbacks)->toHaveLength(0);

            $this->suite->before(function() {});
            $callbacks = $this->suite->callbacks('before');
            expect($callbacks)->toHaveLength(1);

        });

    });

    describe("->after()", function() {

        it("creates a before callback", function() {

            $callbacks = $this->suite->callbacks('after');
            expect($callbacks)->toHaveLength(0);

            $this->suite->after(function() {});
            $callbacks = $this->suite->callbacks('after');
            expect($callbacks)->toHaveLength(1);

        });

    });

    describe("->beforeEach()", function() {

        it("creates a beforeEach callback", function() {

            $callbacks = $this->suite->callbacks('beforeEach');
            expect($callbacks)->toHaveLength(0);

            $this->suite->beforeEach(function() {});
            $callbacks = $this->suite->callbacks('beforeEach');
            expect($callbacks)->toHaveLength(1);

        });

    });

    describe("->afterEach()", function() {

        it("creates a before callback", function() {

            $callbacks = $this->suite->callbacks('afterEach');
            expect($callbacks)->toHaveLength(0);

            $this->suite->afterEach(function() {});
            $callbacks = $this->suite->callbacks('afterEach');
            expect($callbacks)->toHaveLength(1);

        });

    });

    describe("->ddescribe()", function() {

        it("executes only the exclusive `it`", function() {

            $describe = $this->suite->describe("", function() {

                $this->exectuted = ['it' => 0, 'iit' => 0];

                $this->ddescribe("->ddescribe()", function() {

                    $this->it("ddescribe it", function() {
                        $this->exectuted['iit']++;
                    });

                    $this->it("ddescribe it", function() {
                        $this->exectuted['iit']++;
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

            expect($describe->exectuted)->toEqual(['it' => 0, 'iit' => 2]);
            expect($this->suite->exclusive())->toBe(true);
            expect($this->suite->status())->toBe(-1);
            expect($this->suite->passed())->toBe(true);

        });

    });

    describe("->ccontext()", function() {

        it("executes only the exclusive `it`", function() {

            $describe = $this->suite->describe("", function() {

                $this->exectuted = ['it' => 0, 'iit' => 0];

                $this->ccontext("ccontext", function() {

                    $this->it("ccontext it", function() {
                        $this->exectuted['iit']++;
                    });

                    $this->it("ccontext it", function() {
                        $this->exectuted['iit']++;
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

            expect($describe->exectuted)->toEqual(['it' => 0, 'iit' => 2]);
            expect($this->suite->exclusive())->toBe(true);
            expect($this->suite->status())->toBe(-1);
            expect($this->suite->passed())->toBe(true);

        });

    });

    describe("->iit()", function() {

        it("executes only the exclusive `it`", function() {

            $describe = $this->suite->describe("", function() {

                $this->exectuted = ['it' => 0, 'iit' => 0];

                $this->it("an it", function() {
                    $this->exectuted['it']++;
                });

                $this->iit("an iit", function() {
                    $this->exectuted['iit']++;
                });

                $this->it("an it", function() {
                    $this->exectuted['it']++;
                });

                $this->iit("an iit", function() {
                    $this->exectuted['iit']++;
                });

            });

            $this->suite->run();

            expect($describe->exectuted)->toEqual(['it' => 0, 'iit' => 2]);
            expect($this->suite->exclusive())->toBe(true);
            expect($this->suite->status())->toBe(-1);
            expect($this->suite->passed())->toBe(true);

        });

    });

    describe("::hash()", function() {

        it("creates an hash from objects", function() {

            $instance = new stdClass();

            $hash1 = Suite::hash($instance);
            $hash2 = Suite::hash($instance);
            $hash3 = Suite::hash(new stdClass());

            expect($hash1)->toBe($hash2);
            expect($hash1)->not->toBe($hash3);

        });

        it("creates an hash from class names", function() {

            $class = 'hello\world\class';
            $hash = Suite::hash($class);
            expect($hash)->toBe($class);

        });

        it("Throws an exception if values are not string or objects", function() {

            $closure = function() {
                $hash = Suite::hash([]);
            };

            expect($closure)->toThrow(new InvalidArgumentException("Error, the passed argument is not hashable."));

        });

    });

    describe("::register()", function() {

        it("registers an hash", function() {

            $instance = new stdClass();

            $hash = Suite::hash($instance);
            Suite::register($hash);

            expect(Suite::registered($hash))->toBe(true);

        });

    });

    describe("::register()", function() {

        it("return `false` if the hash is not registered", function() {

            $instance = new stdClass();

            $hash = Suite::hash($instance);

            expect(Suite::registered($hash))->toBe(false);

        });

    });

    describe("::clear()", function() {

        it("clears registered hashes", function() {

            $instance = new stdClass();

            $hash = Suite::hash($instance);
            Suite::register($hash);

            expect(Suite::registered($hash))->toBe(true);

            Suite::clear();

            expect(Suite::registered($hash))->toBe(false);

        });

    });

    describe("->status()", function() {

        it("returns `0` if a specs suite passes", function() {

            $describe = $this->suite->describe("", function() {
                $this->it("passes", function() {
                    $this->expect(true)->toBe(true);
                });
            });

            $this->suite->run();
            expect($this->suite->status())->toBe(0);

        });

        it("returns `-1` if a specs suite fails", function() {

            $describe = $this->suite->describe("", function() {
                $this->it("fails", function() {
                    $this->expect(true)->toBe(false);
                });
            });

            $this->suite->run();
            expect($this->suite->status())->toBe(-1);

        });

        it("forces a specified return status", function() {

            $describe = $this->suite->describe("", function() {
                $this->it("passes", function() {
                    $this->expect(true)->toBe(true);
                });
            });

            $this->suite->run();
            expect($this->suite->status())->toBe(0);

            $this->suite->status(-1);
            expect($this->suite->status())->toBe(-1);

        });

    });

    describe("->_process()", function() {

        it("calls `after` callbacks if an exception occurs during callbacks", function() {

            $describe = $this->suite->describe("", function() {

                $this->inAfterEach = 0;

                $this->beforeEach(function() {
                    throw new Exception('Breaking the flow should execute afterEach anyway.');
                });

                $this->it("does nothing", function() {
                });

                $this->afterEach(function() {
                    $this->inAfterEach++;
                });

            });

            $this->suite->run();

            expect($describe->inAfterEach)->toBe(1);

            $results = $this->suite->results();
            expect($results['exceptions'])->toHaveLength(1);

            $exception = end($results['exceptions']);
            $actual = $exception['exception']->getMessage();
            expect($actual)->toBe('Breaking the flow should execute afterEach anyway.');
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
