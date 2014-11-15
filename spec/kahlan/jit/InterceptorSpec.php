<?php
namespace spec\kahlan\jit;

use RuntimeException;

use kahlan\plugin\Stub;
use kahlan\jit\Patchers;
use kahlan\jit\Interceptor;

describe("Interceptor", function() {

    /**
     * Save current & reinitialize the Interceptor class.
     */
    before(function() {
        $this->previous = Interceptor::loader();
    });

    beforeEach(function() {
        $this->autoloader = Stub::create();
        Stub::on($this->autoloader)->methods([
            'loadClass' => [false]
        ]);
    });

    describe("::patch()", function() {

        it("patches the composer autoloader by default", function() {
            Interceptor::unpatch();
            Interceptor::patch();
            expect(Interceptor::original()['0'])->toBeAnInstanceOf("Composer\Autoload\ClassLoader");
            expect(Interceptor::originalInstance())->toBeAnInstanceOf("Composer\Autoload\ClassLoader");
            Interceptor::loader($this->previous);
        });

        it("throws an exception if the autoloader is invalid", function() {
            Interceptor::unpatch();
            $closure = function() {
                Interceptor::patch(['loader' => [$this->autoloader, 'loadClass']]);
            };
            expect($closure)->toThrow(new RuntimeException("The loader option need to be a valid registered autoloader."));
            Interceptor::loader($this->previous);
        });

        it("throws an exception if the autoloader has already been patched", function() {
            $closure = function() {
                Interceptor::patch();
            };
            expect($closure)->toThrow(new RuntimeException("An interceptor is already attached."));
        });

        it("throws an exception if the autoloader has already been patched", function() {
            $composer = Interceptor::composer();

            Interceptor::unpatch();
            spl_autoload_unregister($composer);

            $message = '';
            try {
                Interceptor::patch();
            } catch (RuntimeException $e) {
                $message = $e->getMessage();
            }

            spl_autoload_register($composer);
            Interceptor::loader($this->previous);

            expect($message)->toBe("The loader option need to be a valid autoloader.");
        });

    });

    describe("::unpatch()", function() {

        it("unpatches the patched autoloader", function() {

            $success = Interceptor::unpatch();
            $actual = Interceptor::loader();
            Interceptor::loader($this->previous);

            expect($success)->toBe(true);
            expect($actual)->toBe(null);

        });

        it("returns `false` if there's no patched autoloader", function() {

            Interceptor::unpatch();
            $success = Interceptor::unpatch();
            Interceptor::loader($this->previous);

            expect($success)->toBe(false);

        });

    });

    describe("::loader()", function() {

        it("returns the interceptor autoloader", function() {

            expect(Interceptor::loader()[0])->toBeAnInstanceOf("kahlan\jit\Interceptor");

        });

    });

    describe("::composer()", function() {

        it("returns the composer autoloader", function() {

            expect(Interceptor::composer()[0])->toBeAnInstanceOf("Composer\Autoload\ClassLoader");

        });

    });

    describe("::loadFile()", function() {

        it("loads a file", function() {

            $instance = Interceptor::instance();
            expect($instance->loadFile('spec/fixture/jit/interceptor/ClassA.php'))->toBe(true);
            expect(class_exists('spec\fixture\jit\interceptor\ClassA', false))->toBe(true);

        });

        it("throws an exception for unexisting files", function() {

            $path = 'spec/fixture/jit/interceptor/ClassUnexisting.php';

            $closure= function() use ($path) {
                $instance = Interceptor::instance();
                $instance->loadFile($path);
            };
            expect($closure)->toThrow("`E_WARNING` file_get_contents({$path}): failed to open stream: No such file or directory");

        });

    });

    describe("::loadFiles()", function() {

        context("when there's no patched autoloader reference", function() {

            it("fails to load any file", function() {

                Interceptor::unpatch();
                $loaded = Interceptor::loadFiles(['spec/fixture/jit/interceptor/ClassToNotLoad.php']);
                $exists = class_exists('spec\fixture\jit\interceptor\ClassToNotLoad', false);
                Interceptor::loader($this->previous);

                expect($loaded)->toBe(false);
                expect($exists)->toBe(false);

            });

        });

        it("loads a file", function() {

            expect(Interceptor::loadFiles([
                'spec/fixture/jit/interceptor/ClassB.php',
                'spec/fixture/jit/interceptor/ClassC.php'
            ]))->toBe(true);
            expect(class_exists('spec\fixture\jit\interceptor\ClassB', false))->toBe(true);
            expect(class_exists('spec\fixture\jit\interceptor\ClassC', false))->toBe(true);

        });

    });

    describe("::loadClass()", function() {

        it("loads a class", function() {

            $instance = Interceptor::instance();
            expect($instance->loadClass('spec\fixture\jit\interceptor\ClassD'))->toBe(true);
            expect(class_exists('spec\fixture\jit\interceptor\ClassD', false))->toBe(true);

        });

    });

    describe("::findPath()", function() {

        it("finds a namespace path", function() {

            $instance = Interceptor::instance();
            $expected = realpath('spec/fixture/jit/interceptor');
            expect($instance->findPath('spec\fixture\jit\interceptor'))->toBe($expected);

        });

        it("finds a PHP class path", function() {

            $instance = Interceptor::instance();
            $expected = realpath('spec/fixture/jit/interceptor/ClassA.php');
            expect($instance->findPath('spec\fixture\jit\interceptor\ClassA'))->toBe($expected);

        });

        it("finds a HH class path", function() {

            $instance = Interceptor::instance();
            $expected = realpath('spec/fixture/jit/interceptor/ClassHh.hh');
            expect($instance->findPath('spec\fixture\jit\interceptor\ClassHh'))->toBe($expected);

        });

    });

});