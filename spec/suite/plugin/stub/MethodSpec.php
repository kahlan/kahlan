<?php
namespace kahlan\kahlan\spec\suite\plugin\stub;

use Exception;
use jit\Interceptor;
use kahlan\jit\patcher\Pointcut;
use kahlan\plugin\Stub;

use kahlan\spec\fixture\plugin\pointcut\Foo;

describe("Method", function() {

    /**
     * Save current & reinitialize the Interceptor class.
     */
    before(function() {
        $this->previous = Interceptor::instance();
        Interceptor::unpatch();

        $cachePath = rtrim(sys_get_temp_dir(), DS) . DS . 'kahlan';
        $include = ['kahlan\spec\\'];
        $interceptor = Interceptor::patch(compact('include', 'cachePath'));
        $interceptor->patchers()->add('pointcut', new Pointcut());
    });

    /**
     * Restore Interceptor class.
     */
    after(function() {
        Interceptor::load($this->previous);
    });

    describe("->run()", function() {

        it("should set closure", function() {

            $foo = new Foo();
            $stub = Stub::on($foo)->method('message');
            $stub->run(function($param) {
                return $param;
            });

            expect($foo->message('Aloha!'))->toBe('Aloha!');

        });

        it("should throw when return is already set", function() {

            expect(function() {
                $foo = new Foo();
                $stub = Stub::on($foo)->method('message');
                $stub->andReturn('Ahoy!');

                $stub->run(function($param) {
                    return $param;
                });
            })->toThrow(new Exception('Some return values are already set.'));

        });

        it("should throw when trying to pass non callable", function() {

            expect(function() {
                $foo = new Foo();
                $stub = Stub::on($foo)->method('message');

                $stub->run('String');
            })->toThrow(new Exception('The passed parameter is not callable.'));

        });

    });

});