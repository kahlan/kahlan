<?php
namespace spec\kahlan\jit\patcher;

use jit\Patchers;
use jit\Interceptor;
use kahlan\IncompleteException;
use kahlan\jit\patcher\DummyClass as DummyClassPatcher;
use kahlan\plugin\DummyClass;

describe("DummyClass", function() {

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

                $patchers = new Patchers();
                $patchers->add('substitute', new DummyClassPatcher());
                $cachePath = rtrim(sys_get_temp_dir(), DS) . DS . 'kahlan';
                Interceptor::patch(compact('patchers', 'cachePath'));
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

                $patchers = new Patchers();
                $patchers->add('substitute', new DummyClassPatcher(['namespaces' => ['spec\\']]));
                $cachePath = rtrim(sys_get_temp_dir(), DS) . DS . 'kahlan';
                Interceptor::patch(compact('patchers', 'cachePath'));
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
