<?php
namespace spec\kahlan\jit;

use RuntimeException;

use kahlan\plugin\Stub;
use kahlan\plugin\DummyClass;
use kahlan\jit\Patchers;
use kahlan\jit\Interceptor;

describe("Interceptor", function() {

    /**
     * Save current & reinitialize the Interceptor class.
     */
    before(function() {
        $this->previous = Interceptor::instance();
    });

    beforeEach(function() {
        DummyClass::disable();
        $this->autoloader = Stub::create();
        Stub::on($this->autoloader)->methods([
            'loadClass' => function($class) {
                return $original->loadClass($class);
            },
            'findFile'        => ['some/path/file.php'],
            'getClassMap'     => [['SomeClass' => 'some/path/file.php']],
            'getPrefixes'     => [['psr0\\' => ['some/namespace/path']]],
            'getPrefixesPsr4' => [['psr4\\' => ['some/namespace/path/src']]]
        ]);
    });

    describe("::patch()", function() {

        it("patches the composer autoloader by default", function() {
            Interceptor::unpatch();
            Interceptor::patch();
            $interceptor = Interceptor::instance();
            expect($interceptor->originalInstance())->toBeAnInstanceOf("Composer\Autoload\ClassLoader");
            Interceptor::load($this->previous);
        });

        it("throws an exception if the autoloader is invalid", function() {
            Interceptor::unpatch();
            $closure = function() {
                Interceptor::patch(['loader' => [$this->autoloader, 'loadClass']]);
            };
            expect($closure)->toThrow(new RuntimeException("The loader option need to be a valid registered autoloader."));
            Interceptor::load($this->previous);
        });

        it("throws an exception if the autoloader has already been patched", function() {
            $closure = function() {
                Interceptor::patch();
            };
            expect($closure)->toThrow(new RuntimeException("An interceptor is already attached."));
        });

        it("throws an exception if the autoloader has already been patched", function() {
            Interceptor::unpatch();

            $composer = Interceptor::composer();
            spl_autoload_unregister($composer);

            $message = '';
            try {
                Interceptor::patch();
            } catch (RuntimeException $e) {
                $message = $e->getMessage();
            }

            spl_autoload_register($composer);
            Interceptor::load($this->previous);

            expect($message)->toBe("The loader option need to be a valid autoloader.");
        });

    });

    describe("::unpatch()", function() {

        it("detaches the patched autoloader", function() {
            $success = Interceptor::unpatch();
            $actual = Interceptor::instance();

            Interceptor::load($this->previous);
            expect($success)->toBe(true);
            expect($actual)->toBe(null);
        });

        it("returns `false` if there's no patched autoloader", function() {

            Interceptor::unpatch();
            $success = Interceptor::unpatch();

            Interceptor::load($this->previous);
            expect($success)->toBe(false);

        });

    });

    describe("::instance()", function() {

        it("returns the interceptor autoloader", function() {

            expect(Interceptor::instance())->toBeAnInstanceOf("kahlan\jit\Interceptor");

        });

    });

    describe("::composer()", function() {

        it("returns the composer autoloader", function() {

            Interceptor::unpatch();
            $composer = Interceptor::composer()[0];

            Interceptor::load($this->previous);
            expect($composer)->toBeAnInstanceOf("Composer\Autoload\ClassLoader");

        });

    });

    describe("->findFile()", function() {

        it("deletages finds to patched autoloader", function() {

            //Disable interceptor
            Interceptor::unpatch();

            //Replace the composer autoloader by our custom stub
            $autoloader = [$this->autoloader, 'loadClass'];
            $composer = Interceptor::composer();

            spl_autoload_register($autoloader);
            spl_autoload_unregister($composer);

            //Patch our custom stub
            Interceptor::patch(['loader' => $autoloader]);

            $interceptor = Interceptor::instance();
            $actual = $interceptor->findFile('spec\fixture\jit\interceptor\ClassA');

            //Restore the interceptor & composer back
            Interceptor::unpatch();
            spl_autoload_register($composer);
            spl_autoload_unregister($autoloader);
            Interceptor::load($this->previous);

            //Does it worked ?
            expect($actual)->toBe('some/path/file.php');

        });

        it("still finds path even with no patchers defined", function() {

            Interceptor::unpatch();
            Interceptor::patch();

            $interceptor = Interceptor::instance();
            $expected = realpath('spec/fixture/jit/interceptor/ClassA.php');
            $actual = $interceptor->findFile('spec\fixture\jit\interceptor\ClassA');

            Interceptor::load($this->previous);
            expect($actual)->toBe($expected);

        });

    });

    describe("->loadFile()", function() {

        it("loads a file", function() {

            $interceptor = Interceptor::instance();
            expect($interceptor->loadFile('spec/fixture/jit/interceptor/ClassA.php'))->toBe(true);
            expect(class_exists('spec\fixture\jit\interceptor\ClassA', false))->toBe(true);

        });

        it("throws an exception for unexisting files", function() {

            $path = 'spec/fixture/jit/interceptor/ClassUnexisting.php';

            $closure= function() use ($path) {
                $interceptor = Interceptor::instance();
                $interceptor->loadFile($path);
            };
            expect($closure)->toThrow("`E_WARNING` file_get_contents({$path}): failed to open stream: No such file or directory");

        });

    });

    describe("->loadFiles()", function() {

        it("loads a file", function() {

            $interceptor = Interceptor::instance();
            expect($interceptor->loadFiles([
                'spec/fixture/jit/interceptor/ClassB.php',
                'spec/fixture/jit/interceptor/ClassC.php'
            ]))->toBe(true);
            expect(class_exists('spec\fixture\jit\interceptor\ClassB', false))->toBe(true);
            expect(class_exists('spec\fixture\jit\interceptor\ClassC', false))->toBe(true);

        });

    });

    describe("->loadClass()", function() {

        it("loads a class", function() {

            $interceptor = Interceptor::instance();
            expect($interceptor->loadClass('spec\fixture\jit\interceptor\ClassD'))->toBe(true);
            expect(class_exists('spec\fixture\jit\interceptor\ClassD', false))->toBe(true);

        });

        it("returns null when the class can't be loaded", function() {

            $interceptor = Interceptor::instance();
            expect($interceptor->loadClass('spec\fixture\jit\interceptor\ClassUnexisting'))->toBe(null);

        });

    });

    describe("->findPath()", function() {

        it("finds a namespace path", function() {
            $interceptor = Interceptor::instance();
            $expected = realpath('spec/fixture/jit/interceptor');
            expect($interceptor->findPath('spec\fixture\jit\interceptor'))->toBe($expected);

        });

        it("finds a PHP class path", function() {

            $interceptor = Interceptor::instance();
            $expected = realpath('spec/fixture/jit/interceptor/ClassA.php');
            expect($interceptor->findPath('spec\fixture\jit\interceptor\ClassA'))->toBe($expected);

        });

        it("finds a HH class path", function() {

            $interceptor = Interceptor::instance();
            $expected = realpath('spec/fixture/jit/interceptor/ClassHh.hh');
            expect($interceptor->findPath('spec\fixture\jit\interceptor\ClassHh'))->toBe($expected);

        });

    });

    describe("->getClassMap()", function() {

        it("deletages getClassMap() calls to patched autoloader", function() {

            //Disable interceptor
            Interceptor::unpatch();

            //Replace the composer autoloader by our custom stub
            $autoloader = [$this->autoloader, 'loadClass'];
            $composer = Interceptor::composer();

            spl_autoload_register($autoloader);
            spl_autoload_unregister($composer);

            //Patch our custom stub
            Interceptor::patch(['loader' => $autoloader]);

            $interceptor = Interceptor::instance();
            $actual = $interceptor->getClassMap();

            //Restore the interceptor & composer back
            Interceptor::unpatch();
            spl_autoload_register($composer);
            spl_autoload_unregister($autoloader);
            Interceptor::load($this->previous);

            //Does it worked ?
            expect($actual)->toBe(['SomeClass' => 'some/path/file.php']);

        });

    });

    describe("->getPrefixes()", function() {

        it("deletages getPrefixes() calls to patched autoloader", function() {

            //Disable interceptor
            Interceptor::unpatch();

            //Replace the composer autoloader by our custom stub
            $autoloader = [$this->autoloader, 'loadClass'];
            $composer = Interceptor::composer();

            spl_autoload_register($autoloader);
            spl_autoload_unregister($composer);

            //Patch our custom stub
            Interceptor::patch(['loader' => $autoloader]);

            $interceptor = Interceptor::instance();
            $actual = $interceptor->getPrefixes();

            //Restore the interceptor & composer back
            Interceptor::unpatch();
            spl_autoload_register($composer);
            spl_autoload_unregister($autoloader);
            Interceptor::load($this->previous);

            //Does it worked ?
            expect($actual)->toBe(['psr0\\' => ['some/namespace/path']]);

        });

    });

    describe("->getPrefixesPsr4()", function() {

        it("deletages getPrefixes() calls to patched autoloader", function() {

            //Disable interceptor
            Interceptor::unpatch();

            //Replace the composer autoloader by our custom stub
            $autoloader = [$this->autoloader, 'loadClass'];
            $composer = Interceptor::composer();

            spl_autoload_register($autoloader);
            spl_autoload_unregister($composer);

            //Patch our custom stub
            Interceptor::patch(['loader' => $autoloader]);

            $interceptor = Interceptor::instance();
            $actual = $interceptor->getPrefixesPsr4();

            //Restore the interceptor & composer back
            Interceptor::unpatch();
            spl_autoload_register($composer);
            spl_autoload_unregister($autoloader);
            Interceptor::load($this->previous);

            //Does it worked ?
            expect($actual)->toBe(['psr4\\' => ['some/namespace/path/src']]);

        });

    });

    describe("->patchable()", function() {

        it("returns true by default", function() {

            Interceptor::unpatch();
            Interceptor::patch(['include' => ['*']]);

            $interceptor = Interceptor::instance();
            $actual = $interceptor->patchable('anything\namespace\SomeClass');

            Interceptor::load($this->previous);
            expect($actual)->toBe(true);

        });

        it("returns true if the class match the include", function() {

            Interceptor::unpatch();

            Interceptor::patch(['include' => ['allowed\\']]);

            $interceptor = Interceptor::instance();
            $allowed = $interceptor->patchable('allowed\namespace\SomeClass');
            $notallowed = $interceptor->patchable('notallowed\namespace\SomeClass');

            Interceptor::load($this->previous);
            expect($allowed)->toBe(true);
            expect($notallowed)->toBe(false);

        });

        it("processes exclude first", function() {

            Interceptor::unpatch();

            Interceptor::patch([
                'exclude' => ['namespace\\notallowed\\'],
                'include' => ['namespace\\']
            ]);

            $interceptor = Interceptor::instance();
            $allowed = $interceptor->patchable('namespace\allowed\SomeClass');
            $notallowed = $interceptor->patchable('namespace\notallowed\SomeClass');

            Interceptor::load($this->previous);
            expect($allowed)->toBe(true);
            expect($notallowed)->toBe(false);

        });

    });

    describe("->cache()", function() {

        it("returns the cache path", function() {

            Interceptor::unpatch();
            Interceptor::patch();

            $interceptor = Interceptor::instance();
            $cache = $interceptor->cache();

            Interceptor::load($this->previous);
            expect($cache)->toBe(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kahlan');

        });

        it("returns false when trying to get an unexisting file", function() {

            Interceptor::unpatch();
            Interceptor::patch();

            $interceptor = Interceptor::instance();
            $cache = $interceptor->cache('spec\fixture\jit\interceptor\ClassUnexisting');

            Interceptor::load($this->previous);
            expect($cache)->toBe(false);

        });

        it("caches a file and into a subtree similar to the source location", function() {

            $interceptor = Interceptor::instance();
            $file = realpath('spec/fixture/jit/interceptor/ClassToCache.php');
            $body = file_get_contents($file);

            //Just to be able to remove the directory use a different target
            $target = dirname($file) . DIRECTORY_SEPARATOR . 'solo' . DIRECTORY_SEPARATOR . basename($file);
            $actual = $interceptor->cache($target, $body);
            expect($actual)->toBe($interceptor->cache() . $target);
            unlink($actual);
            rmdir(dirname($actual));

        });

    });

});