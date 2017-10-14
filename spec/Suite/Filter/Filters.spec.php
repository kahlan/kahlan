<?php
namespace Kahlan\Spec\Suite\Filter;

use Exception;
use InvalidArgumentException;
use Kahlan\Filter\Filters;
use Kahlan\Plugin\Double;
use Kahlan\Spec\Fixture\Filter\FilterExample;

describe("Filters", function () {

    beforeAll(function () {
        $this->runtimeFilters = Filters::get();
    });

    beforeEach(function () {
        Filters::reset();
        $this->filter1 = function ($next, $message) {
            return "1" . $next($message) . "1";
        };

        $this->filter2 = function ($next, $message) {
            return "2" . $next($message) . "2";
        };

        $this->noChain = function ($next, $message) {
            return "Hello";
        };

    });

    afterEach(function () {
        Filters::reset();
    });

    afterAll(function () {
        Filters::set($this->runtimeFilters);
    });

    context("with an instance context", function () {

        beforeEach(function () {
            $this->stub = new FilterExample();
        });

        describe("::apply()", function () {

            it("applies a filter", function () {

                Filters::apply($this->stub, 'filterable', $this->filter1);
                expect($this->stub->filterable('World!'))->toBe('1Hello World!1');

            });

            it("applies filters on each call", function () {

                Filters::apply($this->stub, 'filterable', $this->filter1);
                expect($this->stub->filterable('World!'))->toBe('1Hello World!1');
                expect($this->stub->filterable('World!'))->toBe('1Hello World!1');
                expect($this->stub->filterable('World!'))->toBe('1Hello World!1');

            });

            it("applies a filter which break the chain", function () {

                Filters::apply($this->stub, 'filterable', $this->noChain);
                expect($this->stub->filterable('World!'))->toBe("Hello");

            });

            it("applies a custom filter", function () {

                $double = Double::instance();

                allow($double)->toReceive('filterable')->andRun(function () {
                    $closure = function ($next, $message) {
                        return "Hello {$message}";
                    };
                    $custom = function ($next, $message) {
                        $message = "Custom {$message}";
                        return $next($message);
                    };
                    return Filters::run($this, 'filterable', func_get_args(), $closure, [$custom]);
                });
                expect($double->filterable('World!'))->toBe("Hello Custom World!");

            });

            it("applies all filters set on a classname", function () {

                Filters::apply(FilterExample::class, 'filterable', $this->filter1);
                expect($this->stub->filterable('World!'))->toBe('1Hello World!1');

            });

        });

        describe("::detach()", function () {

            it("detaches a filter", function () {

                $id = Filters::apply($this->stub, 'filterable', $this->filter1);
                expect(Filters::detach($id))->toBeAnInstanceOf('Closure');
                expect($this->stub->filterable('World!'))->toBe('Hello World!');

            });

            it("detaches all filters attached to a callable", function () {

                $id = Filters::apply($this->stub, 'filterable', $this->filter1);
                expect(Filters::detach($this->stub, 'filterable'))->toHaveLength(1);
                expect($this->stub->filterable('World!'))->toBe('Hello World!');

            });

            it("throws an Exception when trying to detach an unexisting filter id", function () {

                $closure = function () {
                    Filters::detach('foo\Bar#0000000046feb0630000000176a1b630::baz');
                };
                expect($closure)->toThrow(new Exception("Unexisting `'foo\\Bar#0000000046feb0630000000176a1b630::baz'` filter reference id."));

            });

        });

        describe("::filters()", function () {

            it("gets filters attached to a callable", function () {

                Filters::apply($this->stub, 'filterable', $this->filter1);
                $filters = Filters::filters($this->stub, 'filterable');
                expect($filters)->toBeAn('array')->toHaveLength(1);
                expect(reset($filters))->toBeAnInstanceOf('Closure');

            });

        });

        describe("::enable()", function () {

            it("disables the filter system", function () {

                Filters::apply($this->stub, 'filterable', $this->filter1);
                Filters::enable(false);
                expect($this->stub->filterable('World!'))->toBe('Hello World!');

            });

        });

    });

    context("with a class context", function () {

        beforeEach(function () {
            $this->class = Double::classname();
            allow($this->class)->toReceive('::filterable')->andRun(function () {
                return Filters::run(get_called_class(), 'filterable', func_get_args(), function ($next, $message) {
                    return "Hello {$message}";
                });
            });
        });

        describe("::apply()", function () {

            it("applies a filter and override a parameter", function () {

                $class = $this->class;
                Filters::apply($class, 'filterable', $this->filter1);
                expect($class::filterable('World!'))->toBe('1Hello World!1');

            });

            it("applies a filter and break the chain", function () {

                $class = $this->class;
                Filters::apply($class, 'filterable', $this->noChain);
                expect($class::filterable('World!'))->toBe("Hello");

            });

            it("applies parent classes's filters", function () {

                $class = $this->class;
                $subclass = Double::classname(['extends' => $class]);
                allow($subclass)->toReceive('::filterable')->andRun(function () {
                    return Filters::run(get_called_class(), 'filterable', func_get_args(), function ($next, $message) {
                        return "Hello {$message}";
                    });
                });
                Filters::apply($class, 'filterable', $this->filter2);
                Filters::apply($subclass, 'filterable', $this->filter1);
                expect($subclass::filterable('World!'))->toBe('12Hello World!21');

            });

            it("applies parent classes's filters using cached filters", function () {

                $class = $this->class;
                $subclass = Double::classname(['extends' => $class]);
                allow($subclass)->toReceive('::filterable')->andRun(function () {
                    return Filters::run(get_called_class(), 'filterable', func_get_args(), function ($next, $message) {
                        return "Hello {$message}";
                    });
                });
                Filters::apply($class, 'filterable', $this->filter1);
                Filters::apply($subclass, 'filterable', $this->filter2);
                expect($subclass::filterable('World!'))->toBe('21Hello World!12');
                expect($subclass::filterable('World!'))->toBe('21Hello World!12');

            });

            it("invalidates parent cached filters", function () {

                $class = $this->class;
                $subclass = Double::classname(['extends' => $class]);
                allow($subclass)->toReceive('::filterable')->andRun(function () {
                    return Filters::run(get_called_class(), 'filterable', func_get_args(), function ($next, $message) {
                        return "Hello {$message}";
                    });
                });
                Filters::apply($class, 'filterable', $this->filter1);
                Filters::apply($subclass, 'filterable', $this->filter2);
                expect($subclass::filterable('World!'))->toBe('21Hello World!12');

                Filters::apply($subclass, 'filterable', $this->noChain);
                expect($subclass::filterable('World!'))->toBe("Hello");

            });

            it("applies filters in order", function () {

                $class = $this->class;
                $subclass = Double::classname(['extends' => $class]);
                allow($subclass)->toReceive('::filterable')->andRun(function () {
                    return Filters::run(get_called_class(), 'filterable', func_get_args(), function ($next, $message) {
                        return "Hello {$message}";
                    });
                });
                Filters::apply($subclass, 'filterable', $this->filter1);
                Filters::apply($subclass, 'filterable', $this->filter2);
                expect($subclass::filterable('World!'))->toBe('21Hello World!12');

            });

        });

        describe("::get()", function () {

            it("exports filters setted as a class level", function () {
                Filters::apply($this->class, 'filterable', $this->filter1);
                $filters = Filters::get();
                expect($filters)->toHaveLength(1);
                expect(isset($filters[$this->class . '::filterable']))->toBe(true);
            });

        });

        describe("::set()", function () {

            it("imports class based filters", function () {
                Filters::set([$this->class . '::filterable' => [$this->filter1]]);
                $filters = Filters::get();
                expect($filters)->toHaveLength(1);
                expect(isset($filters[$this->class . '::filterable']))->toBe(true);
            });

        });
    });

    describe("::reset()", function () {

        it("clears all the filters", function () {

            Filters::reset();
            expect(Filters::get())->toBe([]);

        });

    });

});
