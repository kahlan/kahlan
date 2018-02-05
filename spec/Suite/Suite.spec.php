<?php
namespace Kahlan\Spec\Suite;

use stdClass;
use Exception;
use InvalidArgumentException;

use Kahlan\MissingImplementationException;
use Kahlan\PhpErrorException;
use Kahlan\Suite;
use Kahlan\Matcher;
use Kahlan\Reporters;
use Kahlan\Arg;
use Kahlan\Plugin\Double;

describe("Suite", function () {

    beforeEach(function () {
        $this->suite = new Suite(['matcher' => new Matcher()]);
        $this->root = $this->suite->root();
        $this->reporters = new Reporters();
    });

    context("when inspecting flow", function () {

        describe("->beforeAll()", function () {

            $this->nb = 0;

            beforeAll(function () {
                $this->nb++;
            });

            it("passes if `before` has been executed", function () use (&$nb) {

                expect($this->nb)->toBe(1);

            });

            it("passes if `before` has not been executed twice", function () use (&$nb) {

                expect($this->nb)->toBe(1);

            });

        });

        describe("->beforeEach()", function () {

            $this->nb = 0;

            beforeEach(function () {
                $this->nb++;
            });

            it("passes if `beforeEach` has been executed", function () {

                expect($this->nb)->toBe(1);

            });

            it("passes if `beforeEach` has been executed twice", function () {

                expect($this->nb)->toBe(2);

            });

            context("with sub scope", function () {

                it("passes if `beforeEach` has been executed once more", function () {

                    expect($this->nb)->toBe(3);

                });

            });

            it("passes if `beforeEach` has been executed once more", function () {

                expect($this->nb)->toBe(4);

            });

        });

        describe("->afterAll()", function () {

            $this->nb = 0;

            afterAll(function () {
                $this->nb++;
            });

            it("passes if `after` has not been executed", function () {

                expect($this->nb)->toBe(0);

            });

        });

        describe("->afterEach()", function () {

            $this->nb = 0;

            afterEach(function () {
                $this->nb++;
            });

            it("passes if `afterEach` has not been executed", function () {

                expect($this->nb)->toBe(0);

            });

            it("passes if `afterEach` has been executed", function () {

                expect($this->nb)->toBe(1);

            });

            context("with sub scope", function () {

                it("passes if `afterEach` has been executed once more", function () {

                    expect($this->nb)->toBe(2);

                });

            });

            it("passes if `afterEach` has been executed once more", function () {

                expect($this->nb)->toBe(3);

            });

        });

        it("reports errors occuring in describes", function () {

            skipIf(defined('HHVM_VERSION') || PHP_MAJOR_VERSION < 7);

            $describe = $this->root->describe("", function () {
                $undefined++;
            });

            $this->suite->run();

            $results = $this->suite->summary()->logs('errored');
            expect($results)->toHaveLength(1);

            $report = reset($results);

            expect($report->exception()->getMessage())->toBe('`E_NOTICE` Undefined variable: undefined');
            expect($report->type())->toBe('errored');
            expect($report->messages())->toBe(['', '']);

            expect($this->suite->status())->toBe(-1);

        });

    });

    describe("->describe()", function () {

        it("creates a sub suite of specs inside the root suite", function () {

            $describe = $this->root->describe("->method()", function () {});

            expect($describe->message())->toBe('->method()');
            expect($describe->parent())->toBe($this->root);

            $blocks = $this->root->children();
            expect($describe)->toBe(end($blocks));

        });

    });

    describe("->context()", function () {

        it("creates a contextualized suite of specs inside the root suite", function () {

            $context = $this->root->context("->method()", function () {});

            expect($context->message())->toBe('->method()');
            expect($context->parent())->toBe($this->root);

            $blocks = $this->root->children();
            expect($context)->toBe(end($blocks));

        });

    });

    describe("->it()", function () {

        it("creates a spec", function () {

            $this->root->it("does some things", function () {});

            $specs = $this->root->children();
            $it = end($specs);

            expect($it->message())->toBe('it does some things');
            expect($it->parent())->toBe($this->root);

        });

    });

    describe("->beforeAll()", function () {

        it("creates a before callback", function () {

            $callbacks = $this->root->callbacks('beforeAll');
            expect($callbacks)->toHaveLength(0);

            $this->root->beforeAll(function () {});
            $callbacks = $this->root->callbacks('beforeAll');
            expect($callbacks)->toHaveLength(1);

        });

        it("captures errors", function () {

            $describe = $this->root->describe("", function () {

                $this->beforeAll(function () {
                    $undefined++;
                });

                $this->it("it", function () {
                    $this->expect(true)->toBe(true);
                });

            });

            $this->suite->run(['reporters' => $this->reporters]);

            $summary = $this->suite->summary();

            expect($summary->passed())->toBe(0);
            expect($summary->errored())->toBe(1);

            $errors = $summary->logs('errored');

            expect($errors)->toHaveLength(1);
            $log = reset($errors);

            expect($log->exception()->getMessage())->toBe("`E_NOTICE` Undefined variable: undefined");
            expect($this->suite->total())->toBe(1);

            expect($this->suite->active())->toBe(1);
            expect($this->root->focused())->toBe(false);
            expect($this->suite->status())->toBe(-1);

        });

        it("autoclears plugins", function () {

            $describe = $this->root->describe("", function () {

                $double = Double::instance();

                $this->describe("first", function () use ($double) {

                    $this->beforeAll(function () use ($double) {
                        allow($double)->toReceive('hello')->andReturn('world');
                    });

                });

                $this->describe("second", function () use ($double) {

                    $this->it("it", function () use ($double) {
                        $this->expect($double->hello())->not->toBe('world');
                    });

                });

            });

            $this->suite->run([
                'reporters' => $this->reporters,
                'autoclear' => [
                    'Kahlan\Plugin\Monkey',
                    'Kahlan\Plugin\Stub',
                    'Kahlan\Plugin\Quit',
                    'Kahlan\Plugin\Call\Calls'
                ]
            ]);
            $summary = $this->suite->summary();

            expect($summary->passed())->toBe(1);

        });

    });

    describe("->afterAll()", function () {

        it("creates a before callback", function () {

            $callbacks = $this->root->callbacks('afterAll');
            expect($callbacks)->toHaveLength(0);

            $this->root->afterAll(function () {});
            $callbacks = $this->root->callbacks('afterAll');
            expect($callbacks)->toHaveLength(1);

        });

        it("captures errors", function () {

            $describe = $this->root->describe("", function () {

                $this->afterAll(function () {
                    $undefined++;
                });

                $this->it("it", function () {
                    $this->expect(true)->toBe(true);
                });

            });

            $this->suite->run(['reporters' => $this->reporters]);

            $summary = $this->suite->summary();

            expect($summary->passed())->toBe(1);
            expect($summary->errored())->toBe(1);

            $errors = $summary->logs('errored');

            expect($errors)->toHaveLength(1);
            $log = reset($errors);

            expect($log->exception()->getMessage())->toBe("`E_NOTICE` Undefined variable: undefined");
            expect($this->suite->total())->toBe(1);

            expect($this->suite->active())->toBe(1);
            expect($this->root->focused())->toBe(false);
            expect($this->suite->status())->toBe(-1);

        });

    });

    describe("->beforeEach()", function () {

        it("creates a beforeEach callback", function () {

            $callbacks = $this->root->callbacks('beforeEach');
            expect($callbacks)->toHaveLength(0);

            $this->root->beforeEach(function () {});
            $callbacks = $this->root->callbacks('beforeEach');
            expect($callbacks)->toHaveLength(1);

        });

    });

    describe("->afterEach()", function () {

        it("creates a before callback", function () {

            $callbacks = $this->root->callbacks('afterEach');
            expect($callbacks)->toHaveLength(0);

            $this->root->afterEach(function () {});
            $callbacks = $this->root->callbacks('afterEach');
            expect($callbacks)->toHaveLength(1);

        });

    });

    describe("->total()/->active()", function () {

        it("return the total/enabled number of specs", function () {

            $describe = $this->root->describe("", function () {

                $this->exectuted = ['it' => 0, 'fit' => 0];

                $this->describe("fdescribe", function () {

                    $this->it("it", function () {
                        $this->exectuted['it']++;
                    });

                    $this->describe("describe", function () {

                        $this->fit("fit", function () {
                            $this->exectuted['fit']++;
                        });

                        $this->it("it", function () {
                            $this->exectuted['it']++;
                        });

                    });

                });

            });

            expect($this->suite->total())->toBe(3);
            expect($this->suite->active())->toBe(1);

        });

    });

    describe("->fdescribe()", function () {

        it("executes only the `it` in focused mode", function () {

            $describe = $this->root->describe("", function () {

                $this->exectuted = ['it' => 0, 'fit' => 0];

                $this->describe("->describe()", function () {

                    $this->it("it", function () {
                        $this->exectuted['it']++;
                    });

                });

                $this->fdescribe("->fdescribe()", function () {

                    $this->fit("fit", function () {
                        $this->exectuted['fit']++;
                    });

                    $this->it("it", function () {
                        $this->exectuted['it']++;
                    });

                });

            });

            $this->suite->run(['reporters' => $this->reporters]);

            expect($describe->scope()->exectuted)->toEqual(['it' => 0, 'fit' => 1]);
            expect($this->root->focused())->toBe(true);
            expect($this->suite->total())->toBe(3);
            expect($this->suite->active())->toBe(1);
            expect($this->suite->status())->toBe(-1);

        });

        it("executes all `it` in focused mode if no one is focused", function () {

            $describe = $this->root->describe("", function () {

                $this->exectuted = ['it' => 0, 'fit' => 0];

                $this->fdescribe("->fdescribe()", function () {

                    $this->it("assumes fit due to the parent", function () {
                        $this->exectuted['fit']++;
                    });

                    $this->it("assumes fit due to the parent", function () {
                        $this->exectuted['fit']++;
                    });

                });

            });

            $this->suite->run(['reporters' => $this->reporters]);

            expect($describe->scope()->exectuted)->toEqual(['it' => 0, 'fit' => 2]);
            expect($this->root->focused())->toBe(true);
            expect($this->suite->total())->toBe(2);
            expect($this->suite->active())->toBe(2);
            expect($this->suite->status())->toBe(-1);

        });

        it("executes all `it` in focused mode if no one is focused in a nested way", function () {

            $describe = $this->root->describe("", function () {

                $this->exectuted = ['it' => 0, 'fit' => 0];

                $this->fdescribe("->fdescribe()", function () {

                    $this->it("assumes fit due to the parent", function () {
                        $this->exectuted['fit']++;
                    });

                    $this->it("assumes fit due to the parent", function () {
                        $this->exectuted['fit']++;
                    });

                    $this->describe("->describe()", function () {

                        $this->it("assumes fit due to the parent", function () {
                            $this->exectuted['fit']++;
                        });

                        $this->it("assumes fit due to the parent", function () {
                            $this->exectuted['fit']++;
                        });

                    });

                });

            });

            $this->suite->run(['reporters' => $this->reporters]);

            expect($describe->scope()->exectuted)->toEqual(['it' => 0, 'fit' => 4]);
            expect($this->root->focused())->toBe(true);
            expect($this->suite->total())->toBe(4);
            expect($this->suite->active())->toBe(4);
            expect($this->suite->status())->toBe(-1);

        });

    });

    describe("->fcontext()", function () {

        it("executes only the `it` in focused mode", function () {

            $context = $this->root->context("", function () {

                $this->exectuted = ['it' => 0, 'fit' => 0];

                $this->fcontext("->fcontext()", function () {

                    $this->fit("assumes fit due to the parent", function () {
                        $this->exectuted['fit']++;
                    });

                    $this->it("assumes fit due to the parent", function () {
                        $this->exectuted['it']++;
                    });

                });

            });

            $this->suite->run(['reporters' => $this->reporters]);

            expect($context->scope()->exectuted)->toEqual(['it' => 0, 'fit' => 1]);
            expect($this->root->focused())->toBe(true);
            expect($this->suite->total())->toBe(2);
            expect($this->suite->active())->toBe(1);
            expect($this->suite->status())->toBe(-1);

        });

        it("executes all `it` in focused mode if no one is focused", function () {

            $context = $this->root->context("", function () {

                $this->exectuted = ['it' => 0, 'fit' => 0];

                $this->fcontext("->fcontext()", function () {

                    $this->it("assumes fit due to the parent", function () {
                        $this->exectuted['fit']++;
                    });

                    $this->it("assumes fit due to the parent", function () {
                        $this->exectuted['fit']++;
                    });

                });

            });

            $this->suite->run(['reporters' => $this->reporters]);

            expect($context->scope()->exectuted)->toEqual(['it' => 0, 'fit' => 2]);
            expect($this->root->focused())->toBe(true);
            expect($this->suite->total())->toBe(2);
            expect($this->suite->active())->toBe(2);
            expect($this->suite->status())->toBe(-1);

        });

        it("executes all `it` in focused mode if no one is focused in a nested way", function () {

            $context = $this->root->context("", function () {

                $this->exectuted = ['it' => 0, 'fit' => 0];

                $this->fcontext("->fcontext()", function () {

                    $this->it("assumes fit due to the parent", function () {
                        $this->exectuted['fit']++;
                    });

                    $this->it("assumes fit due to the parent", function () {
                        $this->exectuted['fit']++;
                    });

                    $this->context("->context()", function () {

                        $this->it("assumes fit due to the parent", function () {
                            $this->exectuted['fit']++;
                        });

                        $this->it("assumes fit due to the parent", function () {
                            $this->exectuted['fit']++;
                        });

                    });

                });

            });

            $this->suite->run(['reporters' => $this->reporters]);

            expect($context->scope()->exectuted)->toEqual(['it' => 0, 'fit' => 4]);
            expect($this->root->focused())->toBe(true);
            expect($this->suite->total())->toBe(4);
            expect($this->suite->active())->toBe(4);
            expect($this->suite->status())->toBe(-1);

        });

    });

    describe("->fit()", function () {

        it("executes only the focused `it`", function () {

            $describe = $this->root->describe("", function () {

                $this->exectuted = ['it' => 0, 'fit' => 0];

                $this->it("an it", function () {
                    $this->exectuted['it']++;
                });

                $this->fit("an fit", function () {
                    $this->exectuted['fit']++;
                });

                $this->it("an it", function () {
                    $this->exectuted['it']++;
                });

                $this->fit("an fit", function () {
                    $this->exectuted['fit']++;
                });

            });

            $this->suite->run(['reporters' => $this->reporters]);

            expect($describe->scope()->exectuted)->toEqual(['it' => 0, 'fit' => 2]);
            expect($this->root->focused())->toBe(true);
            expect($this->suite->total())->toBe(4);
            expect($this->suite->active())->toBe(2);
            expect($this->suite->status())->toBe(-1);

        });

        it("propagates the exclusivity up to parents", function () {

            $describe = $this->root->describe("", function () {

                $this->exectuted = ['it' => 0, 'fit' => 0];

                $this->fdescribe("fdescribe", function () {

                    $this->describe("describe", function () {

                        $this->it("it", function () {
                            $this->exectuted['it']++;
                        });

                        $this->fit("fit", function () {
                            $this->exectuted['fit']++;
                        });

                    });

                });

            });

            $this->suite->run(['reporters' => $this->reporters]);

            expect($describe->scope()->exectuted)->toEqual(['it' => 0, 'fit' => 1]);
            expect($this->root->focused())->toBe(true);
            expect($this->suite->total())->toBe(2);
            expect($this->suite->active())->toBe(1);
            expect($this->suite->status())->toBe(-1);

        });

        it("propagates the exclusivity up to parents bis", function () {

            $describe = $this->root->describe("", function () {

                $this->exectuted = ['it' => 0, 'fit' => 0];

                $this->describe("fdescribe", function () {

                    $this->it("it", function () {
                        $this->exectuted['it']++;
                    });

                    $this->describe("describe", function () {

                        $this->fit("fit", function () {
                            $this->exectuted['fit']++;
                        });

                        $this->it("it", function () {
                            $this->exectuted['it']++;
                        });

                    });

                });

            });

            $this->suite->run(['reporters' => $this->reporters]);

            expect($describe->scope()->exectuted)->toEqual(['it' => 0, 'fit' => 1]);
            expect($this->root->focused())->toBe(true);
            expect($this->suite->total())->toBe(3);
            expect($this->suite->active())->toBe(1);
            expect($this->suite->status())->toBe(-1);

        });

    });

    describe("->focused()", function () {

        it("returns the references of runned focused specs", function () {

            $describe = $this->root->describe("focused suite", function () {

                $this->exectuted = ['it' => 0, 'fit' => 0];

                $this->it("an it", function () {
                    $this->exectuted['it']++;
                });

                $this->fit("an fit", function () {
                    $this->exectuted['fit']++;
                });

                $this->it("another it", function () {
                    $this->exectuted['it']++;
                });

                $this->fit("another fit", function () {
                    $this->exectuted['fit']++;
                });

            });

            $this->suite->run();

            expect($this->suite->summary()->get('focused'))->toHaveLength(2);

        });

    });

    describe("->xdecribe()", function () {

        it("propagates the exclusion down to children", function () {

            $describe = $this->root->describe("", function () {

                $this->exectuted = [
                    'beforeAll' => 0,
                    'afterAll' => 0,
                    'beforeEach' => 0,
                    'afterEach' => 0,
                    'it' => 0
                ];

                $this->beforeAll(function () {
                    $this->exectuted['beforeAll']++;
                });

                $this->afterAll(function () {
                    $this->exectuted['afterAll']++;
                });

                $this->beforeEach(function () {
                    $this->exectuted['beforeEach']++;
                });

                $this->afterEach(function () {
                    $this->exectuted['afterEach']++;
                });

                $this->it("it1", function () {
                    $this->exectuted['it']++;
                });

                $this->xdescribe("xdescribe", function () {

                    $this->beforeAll(function () {
                        $this->exectuted['beforeAll']++;
                    });

                    $this->afterAll(function () {
                        $this->exectuted['afterAll']++;
                    });

                    $this->beforeEach(function () {
                        $this->exectuted['beforeEach']++;
                    });

                    $this->afterEach(function () {
                        $this->exectuted['afterEach']++;
                    });

                    $this->it("it2", function () {
                        $this->exectuted['it']++;
                    });

                    $this->it("it3", function () {
                        $this->exectuted['it']++;
                    });

                });

            });

            $this->suite->run();

            expect($describe->scope()->exectuted)->toEqual([
                'beforeAll' => 1,
                'afterAll' => 1,
                'beforeEach' => 1,
                'afterEach' => 1,
                'it' => 1
            ]);
            expect($this->suite->total())->toBe(3);
            expect($this->suite->active())->toBe(1);
            expect($this->suite->status())->toBe(0);

        });

    });

    describe("->xcontext()", function () {

        it("propagates the exclusion down to children", function () {

            $describe = $this->root->describe("", function () {

                $this->exectuted = [
                    'beforeAll' => 0,
                    'afterAll' => 0,
                    'beforeEach' => 0,
                    'afterEach' => 0,
                    'it' => 0
                ];

                $this->beforeAll(function () {
                    $this->exectuted['beforeAll']++;
                });

                $this->afterAll(function () {
                    $this->exectuted['afterAll']++;
                });

                $this->beforeEach(function () {
                    $this->exectuted['beforeEach']++;
                });

                $this->afterEach(function () {
                    $this->exectuted['afterEach']++;
                });

                $this->it("it1", function () {
                    $this->exectuted['it']++;
                });

                $this->xcontext("xcontext", function () {

                    $this->beforeAll(function () {
                        $this->exectuted['beforeAll']++;
                    });

                    $this->afterAll(function () {
                        $this->exectuted['afterAll']++;
                    });

                    $this->beforeEach(function () {
                        $this->exectuted['beforeEach']++;
                    });

                    $this->afterEach(function () {
                        $this->exectuted['afterEach']++;
                    });

                    $this->it("it2", function () {
                        $this->exectuted['it']++;
                    });

                    $this->it("it3", function () {
                        $this->exectuted['it']++;
                    });

                });

            });

            $this->suite->run();

            expect($describe->scope()->exectuted)->toEqual([
                'beforeAll' => 1,
                'afterAll' => 1,
                'beforeEach' => 1,
                'afterEach' => 1,
                'it' => 1
            ]);
            expect($this->suite->total())->toBe(3);
            expect($this->suite->active())->toBe(1);
            expect($this->suite->status())->toBe(0);

        });

    });

    describe("->xit()", function () {

        it("skips excluded `it`", function () {

            $describe = $this->root->describe("", function () {

                $this->exectuted = ['it' => 0];

                $this->it("an it", function () {
                    $this->exectuted['it']++;
                });

                $this->xit("an xit", function () {
                    $this->exectuted['it']++;
                });

                $this->it("another it", function () {
                    $this->exectuted['it']++;
                });

            });

            $this->suite->run();

            expect($describe->scope()->exectuted)->toEqual(['it' => 2]);
            expect($this->suite->total())->toBe(3);
            expect($this->suite->active())->toBe(2);
            expect($describe->children()[1]->excluded())->toBe(true);
            expect($this->suite->status())->toBe(0);

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

                $this->it("another it", function () {
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

                $this->it("another it", function () {
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

    describe("::hash()", function () {

        it("creates an hash from objects", function () {

            $instance = new stdClass();

            $hash1 = Suite::hash($instance);
            $hash2 = Suite::hash($instance);
            $hash3 = Suite::hash(new stdClass());

            expect($hash1)->toBe($hash2);
            expect($hash1)->not->toBe($hash3);

        });

        it("creates an hash from class names", function () {

            $class = 'hello\world\class';
            $hash = Suite::hash($class);
            expect($hash)->toBe($class);

        });

        it("Throws an exception if values are not string or objects", function () {

            $closure = function () {
                $hash = Suite::hash([]);
            };

            expect($closure)->toThrow(new InvalidArgumentException("Error, the passed argument is not hashable."));

        });

    });

    describe("::register()", function () {

        it("registers an hash", function () {

            $instance = new stdClass();

            $hash = Suite::hash($instance);
            Suite::register($hash);

            expect(Suite::registered($hash))->toBe(true);

        });

    });

    describe("::register()", function () {

        it("return `false` if the hash is not registered", function () {

            $instance = new stdClass();

            $hash = Suite::hash($instance);

            expect(Suite::registered($hash))->toBe(false);

        });

    });

    describe("::reset()", function () {

        it("clears registered hashes", function () {

            $instance = new stdClass();

            $hash = Suite::hash($instance);
            Suite::register($hash);

            expect(Suite::registered($hash))->toBe(true);

            Suite::reset();

            expect(Suite::registered($hash))->toBe(false);

        });

    });

    describe("->status()", function () {

        it("returns `0` if a specs suite passes", function () {

            $describe = $this->root->describe("", function () {
                $this->it("passes", function () {
                    $this->expect(true)->toBe(true);
                });
            });

            $this->suite->run();
            expect($this->suite->status())->toBe(0);

        });

        it("returns `-1` if a specs suite fails", function () {

            $describe = $this->root->describe("", function () {
                $this->it("fails", function () {
                    $this->expect(true)->toBe(false);
                });
            });

            $this->suite->run();
            expect($this->suite->status())->toBe(-1);

        });

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

        it("calls `afterEach` callbacks if an exception occurs during callbacks", function () {

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

        it("logs error if an exception is occuring during an `afterEach` callbacks", function () {

            $describe = $this->root->describe("", function () {

                $this->it("does nothing", function () {
                });

                $this->afterEach(function () {
                    throw new Exception('Errors occured in afterEach should be logged anyway.');
                });

            });

            $this->suite->run();

            $results = $this->suite->summary()->logs('errored');

            expect($results)->toHaveLength(1);

            $report = reset($results);
            $actual = $report->exception()->getMessage();
            expect($actual)->toBe('Errors occured in afterEach should be logged anyway.');

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

    describe("->_errorHandler()", function () {

        it("converts E_NOTICE error to an exception", function () {

            $closure = function () {
                $a = $b;
            };
            expect($closure)->toThrow(new PhpErrorException("`E_NOTICE` Undefined variable: b"));

        });

        it("converts E_WARNING error to an exception", function () {

            $closure = function () {
                $a = array_merge();
            };
            expect($closure)->toThrow(new PhpErrorException("`E_WARNING` array_merge() expects at least 1 parameter, 0 given"));

        });

        it("uses default error reporting settings", function () {

            $describe = $this->root->describe("", function () {

                $this->describe("->_errorHandler()", function () {

                    $this->it("ignores E_NOTICE", function () {
                        $closure = function () {
                            $a = $b;
                        };
                        $this->expect($closure)->not->toThrow();
                    });

                });

            });

            error_reporting(E_ALL ^ E_NOTICE);
            $this->suite->run();
            error_reporting(E_ALL);

            expect($this->suite->status())->toBe(0);

        });

        it('ignores supressed errors', function () {

            $closure = function () {
                $failing = function () {
                    $a = $b;
                };
                @$failing();
            };
            expect($closure)->not->toThrow();
        });

    });

    describe("->reporters()", function () {

        it("returns the reporters", function () {

            $describe = $this->root->describe("", function () {});

            $reporters = Double::instance();
            $this->suite->run(['reporters' => $reporters]);

            expect($this->suite->reporters())->toBe($reporters);

        });

    });

    describe("->stop()", function () {

        it("sends the stop event", function () {

            $describe = $this->root->describe("", function () {});

            $reporters = Double::instance();

            expect($reporters)->toReceive('dispatch')->with('stop', Arg::toBeAnInstanceOf('Kahlan\Summary'));

            $this->suite->run(['reporters' => $reporters]);
            $this->suite->stop();

            expect($this->suite->reporters())->toBe($reporters);

        });

    });

});
