<?php
namespace Kahlan\Kahlan\Spec\Suite\Plugin\Stub;

use Exception;
use Kahlan\Jit\Interceptor;
use Kahlan\Jit\Patcher\Pointcut as PointcutPatcher;
use Kahlan\Jit\Patcher\Monkey as MonkeyPatcher;
use Kahlan\Plugin\Stub;

use Kahlan\Spec\Fixture\Plugin\Monkey\Mon;

describe("Fct", function() {

    /**
     * Save current & reinitialize the Interceptor class.
     */
    before(function() {
        $this->previous = Interceptor::instance();
        Interceptor::unpatch();

        $cachePath = rtrim(sys_get_temp_dir(), DS) . DS . 'kahlan';
        $include = ['Kahlan\Spec\\'];
        $interceptor = Interceptor::patch(compact('include', 'cachePath'));
        $interceptor->patchers()->add('pointcut', new PointcutPatcher());
        $interceptor->patchers()->add('monkey', new MonkeyPatcher());
    });

    /**
     * Restore Interceptor class.
     */
    after(function() {
        Interceptor::load($this->previous);
    });

    describe("->andReturn()", function() {

        it("sets a return value", function() {

            $stub = allow('time')->toBeCalled();
            $stub->andReturn(123);

            $mon = new Mon();
            expect($mon->time())->toBe(123);
            expect($stub->actualReturn())->toBe(123);

        });

        it("sets a return values", function() {

            $stub = allow('time')->toBeCalled();
            $stub->andReturn(123, 456);

            $mon = new Mon();
            expect($mon->time())->toBe(123);
            expect($stub->actualReturn())->toBe(123);

            expect($mon->time())->toBe(456);
            expect($mon->time())->toBe(456);
            expect($stub->actualReturn())->toBe(456);

        });

        it("throws when return is already set", function() {

            expect(function() {
                $stub = allow('time')->toBeCalled();
                $stub->andReturnUsing(function() {
                    return 456;
                });
                $stub->andReturn(123);

            })->toThrow(new Exception('Closure already set.'));

        });

    });

    describe("->andReturnUsing()", function() {

        it("sets a closure", function() {

            $stub = allow('time')->toBeCalled();
            $stub->andReturnUsing(function() {
                return 123;
            });

            $mon = new Mon();
            expect($mon->time())->toBe(123);
            expect($stub->actualReturn())->toBe(123);

        });

        it("sets closures", function() {

            $stub = allow('time')->toBeCalled();
            $stub->andReturnUsing(function() {
                return 123;
            }, function() {
                return 456;
            });

            $mon = new Mon();
            expect($mon->time())->toBe(123);
            expect($stub->actualReturn())->toBe(123);

            expect($mon->time())->toBe(456);
            expect($mon->time())->toBe(456);
            expect($stub->actualReturn())->toBe(456);


        });

        it("throws when return is already set", function() {

            expect(function() {
                $stub = allow('time')->toBeCalled();
                $stub->andReturn(123);

                $stub->andReturnUsing(function() {
                    return 456;
                });
            })->toThrow(new Exception('Some return values are already set.'));

        });

        it("throws when trying to pass non callable", function() {

            expect(function() {
                $stub = allow('time')->toBeCalled();
                $stub->andReturnUsing('String');
            })->toThrow(new Exception('The passed parameter is not callable.'));

        });

    });

});