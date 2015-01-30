<?php
namespace kahlan\spec\suite\jit\patcher;

use jit\Interceptor;
use kahlan\IncompleteException;
use kahlan\jit\patcher\DummyClass as DummyClassPatcher;
use kahlan\plugin\DummyClass;
use kahlan\plugin\Stub;

describe("DummyClass", function() {

    it("should construct and find namespaces", function() {

        $dummy = new DummyClassPatcher([
            'namespaces' => [
                '\kahlan\spec\mock\plugin\stub',
            ]
        ]);
        expect($dummy)->toBeAnInstanceOf('\kahlan\jit\patcher\DummyClass');


        $loader = Stub::create();
        $find = $dummy->findFile($loader, '\kahlan\spec\mock\plugin\stub', null);

        expect($find)->toBeAnInstanceOf('\kahlan\spec\plugin\stub\Stub0');
    });


    describe("->create()", function() {

        /**
         * Warning: with a no namespaces limitation configuration all is_callable will
         * return true which can have some side effects.
         */
        context("when no namespaces limitation is set", function() {
            /**
             * Save current & reinitialize the Interceptor class.
             */
            before(function() {
                $this->previous = Interceptor::instance();
                Interceptor::unpatch();

                $cachePath = rtrim(sys_get_temp_dir(), DS) . DS . 'kahlan';
                $include = ['kahlan\\spec\\'];
                $interceptor = Interceptor::patch(compact('include', 'cachePath'));
                $interceptor->patchers()->add('substitute', new DummyClassPatcher());

            });

            /**
             * Restore Interceptor class.
             */
            after(function() {
                Interceptor::load($this->previous);
            });

            it("throws an IncompleteException when creating an unexisting class", function() {

                $closure = function() {
                    $mock = new Abcd();
                    $mock->helloWorld();
                };
                expect($closure)->toThrow(new IncompleteException);

            });

            it("allows magic call static on live mock", function() {

                expect(function(){ Abcd::helloWorld(); })->toThrow(new IncompleteException);

            });

            it("makes `class_exists` to return `true` when enabled", function() {

                $closure = function() {
                    return class_exists('KahlanDummyClass1');
                };

                $result = $closure();
                expect($result)->toBe(true);

            });

            it("allows `class_exists` to return `false` when disabled", function() {

                DummyClass::disable();

                $closure = function() {
                    return class_exists('KahlanDummyClass2');
                };

                $result = $closure();
                expect($result)->toBe(false);

            });

        });

        context("when limiting to a specific namespace", function() {
            /**
             * Save current & reinitialize the Interceptor class.
             */
            before(function() {
                $this->previous = Interceptor::instance();
                Interceptor::unpatch();

                $cachePath = rtrim(sys_get_temp_dir(), DS) . DS . 'kahlan';
                $include = ['kahlan\\spec\\'];
                $interceptor = Interceptor::patch(compact('include', 'cachePath'));
                $interceptor->patchers()->add('substitute', new DummyClassPatcher(['namespaces' => ['spec\\']]));
            });

            /**
             * Restore Interceptor class.
             */
            after(function() {
                Interceptor::load($this->previous);
            });

            it("throws an IncompleteException when creating an unexisting class", function() {

                $closure = function() {
                    $mock = new Abcd();
                    $mock->helloWorld();
                };
                expect($closure)->toThrow(new IncompleteException);

            });

            it("allows magic call static on live mock", function() {

                expect(function(){ Abcd::helloWorld(); })->toThrow(new IncompleteException);

            });

        });

    });

});
