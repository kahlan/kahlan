<?php
namespace Kahlan\Spec\Jit\Suite;

use Kahlan\Dir\Dir;
use Kahlan\Jit\JitException;
use Kahlan\Jit\Patchers;
use Kahlan\Jit\ClassLoader;

use Kahlan\Spec\Proxy\Autoloader;
use Kahlan\Spec\Mock\Patcher;

describe("ClassLoader", function () {

    beforeEach(function () {
        $this->cachePath = Dir::tempnam(null, 'cache');
        $this->loader = new ClassLoader();
        $this->loader->addPsr4('Kahlan\\', 'src');
        $this->loader->addPsr4('Kahlan\Spec\\', 'spec');
        $this->loader->register(true);
        class_exists('Kahlan\Plugin\Pointcut');
        class_exists('Kahlan\Plugin\Call\Calls');
        class_exists('Kahlan\Jit\Parser');
        class_exists('Kahlan\Jit\JitException');
        class_exists('Kahlan\Jit\Node\BlockDef');
        class_exists('Kahlan\Jit\Node\FunctionDef');
        class_exists('Kahlan\Jit\TokenStream');
    });

    afterEach(function () {
        $this->loader->unpatch();
        $this->loader->unregister();
        if (file_exists($this->cachePath)) {
            Dir::remove($this->cachePath);
        }
    });

    describe("::instance()", function () {

        it("returns the interceptor autoloader", function () {

            expect(ClassLoader::instance())->toBe($this->loader);

        });

    });

    describe("->patch()", function () {

        it("clears caches if `'clearCache'` is `true`", function () {

            touch($this->cachePath . DS . 'CachedFile.php');

            $this->loader->patch([
                'cachePath'  => $this->cachePath,
                'clearCache' => true
            ]);
            expect(file_exists($this->cachePath . DS . 'CachedFile.php'))->toBe(false);

        });

        it("initializes watched files if passed to the constructor", function () {
            $this->temp = Dir::tempnam(null, 'cache');

            touch($this->temp . DS . 'watched1.php');
            touch($this->temp . DS . 'watched2.php');

            $watched = [
                $this->temp . DS . 'watched1.php',
                $this->temp . DS . 'watched2.php'
            ];

            $this->loader->patch([
                'cachePath'  => $this->cachePath,
                'watch' => $watched
            ]);

            expect($this->loader->watched())->toBe($watched);

            Dir::remove($this->temp);
        });

    });

    describe("->findFile()", function () {

        it("finds files", function () {

            $actual = $this->loader->findFile('Kahlan\Spec\Fixture\Jit\Interceptor\ClassA');
            expect($actual)->toBe(realpath('spec/Fixture/Jit/Interceptor/ClassA.php'));

        });

        context("with some patchers defined", function () {

            beforeEach(function () {

                $this->loader->patch(['cachePath' => $this->cachePath]);

                $this->patcher1 = new Patcher();
                $this->patcher2 = new Patcher();

                $this->patchers = $this->loader->patchers();
                $this->patchers->add('patch1', $this->patcher1);
                $this->patchers->add('patch2', $this->patcher2);

            });

            it("delegates find to patchers", function () {

                $expected = realpath('spec/Fixture/Jit/Interceptor/ClassA.php');

                allow($this->patcher1)->toReceive('findFile')->andRun(function ($interceptor, $c, $file) {
                    return $file . '1';
                });
                allow($this->patcher2)->toReceive('findFile')->andRun(function ($interceptor, $c, $file) {
                    return $file . '2';
                });

                $actual = $this->loader->findFile('Kahlan\Spec\Fixture\Jit\Interceptor\ClassA');
                $this->loader->unpatch();
                expect($actual)->toBe($expected . '12');

            });

        });

    });

    describe("->loadFile()", function () {

        beforeEach(function () {
            $this->loadFileNamespacePath = Dir::tempnam(null, 'loadFileNamespace');

            $this->loader->addPsr4('loadFileNamespace\\', $this->loadFileNamespacePath);
            $this->loader->patch(['cachePath' => $this->loadFileNamespacePath]);

            $this->classBuilder = function ($name) {
                return "<?php namespace loadFileNamespace; class {$name} {} ?>";
            };
        });

        afterEach(function () {
            Dir::remove($this->loadFileNamespacePath);
        });

        context("when loader doesn't watch additional files", function () {

            it("loads a file", function () {

                $sourcePath = $this->loadFileNamespacePath . DS . 'ClassA.php';
                file_put_contents($sourcePath, $this->classBuilder('ClassA'));

                expect($this->loader->loadFile($sourcePath))->toBe(true);
                expect(class_exists('loadFileNamespace\ClassA', false))->toBe(true);

            });

            it("loads a file when no patchers are set", function () {

                $this->loader->unpatch();

                $sourcePath = $this->loadFileNamespacePath . DS . 'ClassB.php';
                file_put_contents($sourcePath, $this->classBuilder('ClassB'));

                expect($this->loader->loadFile($sourcePath))->toBe(true);
                expect(class_exists('loadFileNamespace\ClassB', false))->toBe(true);

            });

            it("loads cached files", function () {

                $sourcePath = $this->loadFileNamespacePath . DS . 'ClassCached.php';
                $body = $this->classBuilder('ClassCached');
                file_put_contents($sourcePath, $body);
                $sourceTimestamp = filemtime($sourcePath);
                $this->loader->cache($sourcePath, $body, $sourceTimestamp + 1);

                expect($this->loader->loadFile($sourcePath))->toBe(true);
                expect(class_exists('loadFileNamespace\ClassCached', false))->toBe(true);

            });

            it("throws an exception for unexisting files", function () {

                $path = $this->loadFileNamespacePath . DS . 'ClassUnexisting.php';

                $closure= function () use ($path) {
                    $loader = ClassLoader::instance();
                    $loader->loadFile($path);
                };
                expect($closure)->toThrow("Error, the file `'{$path}'` doesn't exist.");

            });

            it("caches a loaded files and set the cached file motification time to be the same as the source file", function () {

                $sourcePath = $this->loadFileNamespacePath . DS . 'ClassC.php';
                file_put_contents($sourcePath, $this->classBuilder('ClassC'));

                $currentTimestamp = time();
                $sourceTimestamp = $currentTimestamp - 5 * 60;

                touch($sourcePath, $sourceTimestamp);

                expect($this->loader->loadFile($sourcePath))->toBe(true);
                expect(class_exists('loadFileNamespace\ClassB', false))->toBe(true);

                $cacheTimestamp = filemtime($this->loader->cachePath() . DS . ltrim(preg_replace('~:~', '', $sourcePath), DS));
                expect($sourceTimestamp)->toBe($cacheTimestamp - 1);

            });

        });

        context("when the loader watch some additional files", function () {

            beforeEach(function () {
                $this->currentTimestamp = time();
                $this->watched1Timestamp = $this->currentTimestamp - 1 * 60;
                $this->watched2Timestamp = $this->currentTimestamp - 2 * 60;

                touch($this->loadFileNamespacePath . DS . 'watched1.php', $this->watched1Timestamp);
                touch($this->loadFileNamespacePath . DS . 'watched2.php', $this->watched2Timestamp);

                $this->loader->watch([
                    $this->loadFileNamespacePath . DS . 'watched1.php',
                    $this->loadFileNamespacePath . DS . 'watched2.php'
                ]);
            });

            it("caches a file and set the cached file motification time to be the max timestamp between the watched and the source file", function () {

                file_put_contents($this->loadFileNamespacePath . DS . 'ClassD.php', $this->classBuilder('ClassD'));

                $sourceTimestamp = $this->currentTimestamp - 5 * 60;

                touch($this->loadFileNamespacePath . DS . 'ClassD.php', $sourceTimestamp);

                expect($this->loader->loadFile($this->loadFileNamespacePath . DS . 'ClassD.php'))->toBe(true);
                expect(class_exists('loadFileNamespace\ClassD', false))->toBe(true);

                $cacheTimestamp = filemtime($this->loader->cachePath() . DS . ltrim(preg_replace('~:~', '', $this->loadFileNamespacePath), DS) . DS . 'ClassD.php');
                expect($this->watched1Timestamp)->toBe($cacheTimestamp - 1);

            });

        });

    });

    describe("->loadFiles()", function () {

        it("loads a file", function () {

            $this->loader->patch(['cachePath' => $this->cachePath]);

            expect($this->loader->loadFiles([
                'spec/Fixture/Jit/Interceptor/ClassB.php',
                'spec/Fixture/Jit/Interceptor/ClassC.php'
            ]))->toBe(true);
            expect(class_exists('Kahlan\Spec\Fixture\Jit\Interceptor\ClassB', false))->toBe(true);
            expect(class_exists('Kahlan\Spec\Fixture\Jit\Interceptor\ClassC', false))->toBe(true);

        });

    });

    describe("->loadClass()", function () {

        it("loads a class", function () {

            $this->loader->patch(['cachePath' => $this->cachePath]);

            expect($this->loader->loadClass('Kahlan\Spec\Fixture\Jit\Interceptor\ClassD'))->toBe(true);
            expect(class_exists('Kahlan\Spec\Fixture\Jit\Interceptor\ClassD', false))->toBe(true);

        });

        it("bails out the patching process if the class has been excluded from being patched", function () {

            $this->loader->patch([
                'include' => ['allowed\\'],
                'cachePath' => $this->cachePath
            ]);

            $cached = $this->cachePath . realpath('spec/Fixture/Jit/Interceptor/ClassE.php');
            $this->loader->loadClass('Kahlan\Spec\Fixture\Jit\Interceptor\ClassE');
            expect(file_exists($cached))->toBe(false);

        });

        it("loads and proccess patchable class", function () {

            $this->loader->patch(['cachePath' => $this->cachePath]);
            $patcher = new Patcher();
            $this->loader->patchers()->add('patcher', $patcher);

            expect($this->loader->loadClass('Kahlan\Spec\Fixture\Jit\Interceptor\ClassF'))->toBe(true);
            expect(class_exists('Kahlan\Spec\Fixture\Jit\Interceptor\ClassF', false))->toBe(true);

        });

        it("returns null when the class can't be loaded", function () {

            $this->loader->patch(['cachePath' => $this->cachePath]);

            expect($this->loader->loadClass('Kahlan\Spec\Fixture\Jit\Interceptor\ClassUnexisting'))->toBe(null);

        });

    });

    describe("->findPath()", function () {

        it("finds a namespace path", function () {

            $this->loader->patch(['cachePath' => $this->cachePath]);

            $expected = realpath('spec/Fixture/Jit/Interceptor');
            expect($this->loader->findPath('Kahlan\Spec\Fixture\Jit\Interceptor'))->toBe($expected);

        });

        it("finds a PHP class path", function () {

            $this->loader->patch(['cachePath' => $this->cachePath]);

            $expected = realpath('spec/Fixture/Jit/Interceptor/ClassA.php');
            expect($this->loader->findPath('Kahlan\Spec\Fixture\Jit\Interceptor\ClassA'))->toBe($expected);

        });

        it("finds a HH class path", function () {

            $this->loader->patch(['cachePath' => $this->cachePath]);

            $expected = realpath('spec/Fixture/Jit/Interceptor/ClassHh.hh');
            expect($this->loader->findPath('Kahlan\Spec\Fixture\Jit\Interceptor\ClassHh'))->toBe($expected);

        });

        it("gives precedence to files", function () {

            $this->loader->patch(['cachePath' => $this->cachePath]);

            $expected = realpath('spec/Fixture/Jit/Interceptor/ClassA.php');
            expect($this->loader->findPath('Kahlan\Spec\Fixture\Jit\Interceptor\ClassA'))->toBe($expected);

        });

        it("forces the returned path to be a directory", function () {

            $this->loader->patch(['cachePath' => $this->cachePath]);

            $expected = realpath('spec/Fixture/Jit/Interceptor/ClassA');
            expect($this->loader->findPath('Kahlan\Spec\Fixture\Jit\Interceptor\ClassA', true))->toBe($expected);

        });

    });

    describe("->allowed()", function () {

        it("returns true by default", function () {

            $this->loader->patch([
                'include' => ['*'],
                'cachePath' => $this->cachePath
            ]);

            $actual = $this->loader->allowed('anything\namespace\SomeClass');
            expect($actual)->toBe(true);

        });

        it("returns true if the class match the include", function () {

            $this->loader->patch([
                'include' => ['allowed\\'],
                'cachePath' => $this->cachePath
            ]);

            $allowed = $this->loader->allowed('allowed\namespace\SomeClass');
            $notallowed = $this->loader->allowed('notallowed\namespace\SomeClass');

            expect($allowed)->toBe(true);
            expect($notallowed)->toBe(false);

        });

        it("processes exclude first", function () {

            $this->loader->patch([
                'exclude' => ['namespace\\notallowed\\'],
                'include' => ['namespace\\'],
                'cachePath' => $this->cachePath
            ]);

            $allowed = $this->loader->allowed('namespace\allowed\SomeClass');
            $notallowed = $this->loader->allowed('namespace\notallowed\SomeClass');

            expect($allowed)->toBe(true);
            expect($notallowed)->toBe(false);

        });

    });

    describe("->cachePath()", function () {

        it("returns the cache path", function () {

            $this->loader->patch();

            $path = $this->loader->cachePath();

            expect($path)->toBe(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'jit');

        });

    });

    describe("->cache()", function () {

        it("throws an exception if no cache has been disabled", function () {

            $this->temp = Dir::tempnam(null, 'cache');

            $closure = function () {
                $this->loader->patch(['cachePath' => false]);
                $this->loader->cache($this->temp . DS . 'ClassToCache.php', '');
            };

            expect($closure)->toThrow(new JitException('Error, any cache path has been defined.'));

            Dir::remove($this->temp);
        });

        context("with a valid cache path", function () {

            beforeEach(function () {
                $this->loader->patch(['cachePath' => $this->cachePath]);
                $this->temp = Dir::tempnam(null, 'cache');

            });

            afterEach(function () {
                Dir::remove($this->temp);
            });

            it("caches a file and into a subtree similar to the source location", function () {

                $path = $this->temp . DS . 'ClassToCache.php';
                $cached = $this->loader->cache($path, '');
                expect($cached)->toBe($this->loader->cachePath() . DS . ltrim(preg_replace('~:~', '', $path), DS));

            });

        });

    });

    describe("->cached()", function () {

        it("returns false when trying to get an unexisting file", function () {

            $this->loader->patch(['cachePath' => $this->cachePath]);

            $cached = $this->loader->cached('an\arbitrary\path\ClassUnexisting');

            expect($cached)->toBe(false);

        });

        it("returns false when trying no cache path has been defined", function () {

            $this->loader->patch(['cachePath' => false]);

            $cached = $this->loader->cached('an\arbitrary\path\ClassUnexisting');

            expect($cached)->toBe(false);

        });

        context("when the loader doesn't watch some additional files", function () {

            beforeEach(function () {
                $this->loader->patch(['cachePath' => $this->cachePath]);

                $this->temp = Dir::tempnam(null, 'cache');
                $this->cached = $this->loader->cache($this->temp . DS . 'CachedClass.php', '');
            });

            afterEach(function () {
                Dir::remove($this->temp);
            });

            it("returns the cached file path if the modified timestamp of the cached file is up to date", function () {
                touch($this->temp . DS . "CachedClass.php", time() - 1);
                expect($this->loader->cached($this->temp . DS . 'CachedClass.php'))->not->toBe(false);
            });

            it("returns false if the modified timestamp of the cached file is outdated", function () {
                touch($this->temp . DS . "CachedClass.php", time() + 1);
                expect($this->loader->cached($this->temp . DS . 'CachedClass.php'))->toBe(false);
            });

        });

        context("when the loader watch some additional files", function () {

            beforeEach(function () {
                $this->loader->patch(['cachePath' => $this->cachePath]);

                $this->temp = Dir::tempnam(null, 'cache');
                $this->cached = $this->loader->cache($this->temp . DS . 'CachedClass.php', '');
            });

            afterEach(function () {
                Dir::remove($this->temp);
            });

            it("returns the cached file path if the modified timestamp of the cached file is up to date", function () {

                $time = time();
                touch($this->temp . DS . 'watched1.php', $time - 1);
                touch($this->temp . DS . 'watched2.php', $time - 1);
                touch($this->temp . DS . 'CachedClass.php', $time - 1);

                $this->loader->watch([$this->temp . DS . 'watched1.php', $this->temp . DS . 'watched2.php']);

                expect($this->loader->cached($this->temp . DS . 'CachedClass.php'))->not->toBe(false);
            });

            it("returns false if the modified timestamp of the cached file is outdated", function () {

                $time = time();
                touch($this->temp . DS . 'watched1.php', $time - 1);
                touch($this->temp . DS . 'watched2.php', $time - 1);
                touch($this->temp . DS . 'CachedClass.php', $time + 1);

                $this->loader->watch([$this->temp . DS . 'watched1.php', $this->temp . DS . 'watched2.php']);

                expect($this->loader->cached($this->temp . DS . 'CachedClass.php'))->toBe(false);
            });

            it("returns false if the modified timestamp of a watched file is outdated", function () {

                $time = time();
                touch($this->temp . DS . 'watched1.php', $time - 1);
                touch($this->temp . DS . 'watched2.php', $time + 1);
                touch($this->temp . DS . 'CachedClass.php', $time - 1);

                $this->loader->watch([$this->temp . DS . 'watched1.php', $this->temp . DS . 'watched2.php']);

                expect($this->loader->cached($this->temp . DS . 'CachedClass.php'))->toBe(false);

                touch($this->temp . DS . 'watched1.php', $time + 1);
                touch($this->temp . DS . 'watched2.php', $time - 1);
                touch($this->temp . DS . 'CachedClass.php', $time - 1);

                $this->loader->watch([$this->temp . DS . 'watched1.php', $this->temp . DS . 'watched2.php']);

                expect($this->loader->cached($this->temp . DS . 'CachedClass.php'))->toBe(false);
            });

        });

    });

    describe("->clearCache()", function () {

        beforeEach(function () {
            $this->customCachePath = Dir::tempnam(null, 'cache');
            $this->loader->patch(['cachePath' => $this->customCachePath]);

            $this->temp = Dir::tempnam(null, 'cache');
            $this->loader->cache($this->temp . DS . 'CachedClass1.php', '');
            $this->loader->cache($this->temp . DS . 'nestedDir/CachedClass2.php', '');
        });

        afterEach(function () {
            Dir::remove($this->temp);
        });

        it("bails out when no cache directory exists", function () {

            Dir::remove($this->customCachePath);
            $this->loader->clearCache();
            expect($this->loader->clearCache())->toBe(null);

        });

        it("clears the cache", function () {

            $this->loader->clearCache();
            expect(array_diff(scandir($this->customCachePath), array('.', '..')))->toBe([]);

        });

        it("bails out if the cache has already been cleared", function () {

            $this->loader->clearCache();
            $this->loader->clearCache();
            expect(array_diff(scandir($this->customCachePath), array('.', '..')))->toBe([]);

        });

    });

    describe("->watch()/unwatch()", function () {

        it("adds some file to be watched", function () {

            $this->temp = Dir::tempnam(null, 'cache');

            touch($this->temp . DS . 'watched1.php');
            touch($this->temp . DS . 'watched2.php');

            $watched = [
                $this->temp . DS . 'watched1.php',
                $this->temp . DS . 'watched2.php'
            ];

            $this->loader->patch(['cachePath' => $this->cachePath]);

            $this->loader->watch($this->temp . DS . 'watched1.php');
            expect($this->loader->watched())->toBe([$watched[0]]);

            $this->loader->watch($this->temp . DS . 'watched2.php');
            expect($this->loader->watched())->toBe($watched);

            $this->loader->unwatch($this->temp . DS . 'watched1.php');
            expect($this->loader->watched())->toBe([$watched[1]]);

            $this->loader->unwatch($this->temp . DS . 'watched2.php');
            expect($this->loader->watched())->toBe([]);

            Dir::remove($this->temp);

        });

    });

    describe("->files()", function () {

        it("gets/sets files", function () {

            $this->loader->files([
                '337663d83d8353cc8c7847676b3b0937' => '/src/functions.php',
            ]);

            expect($this->loader->files())->toBe([
                '337663d83d8353cc8c7847676b3b0937' => '/src/functions.php',
            ]);

        });

    });

    describe("->prefixes()", function () {

        it("gets all prefixes", function () {

            $this->loader->add('Psr0', 'psr0');

            expect($this->loader->prefixes())->toBe([
                'Kahlan\\' => ['src'],
                'Kahlan\\Spec\\' => ['spec'],
                'Psr0' => ['psr0/Psr0']
            ]);

        });

    });

    describe("->getPrefixes()", function () {

        it("gets prefixes", function () {

            $this->loader->add('Psr0', 'psr0');

            expect($this->loader->getPrefixes())->toBe([
                'Psr0' => ['psr0']
            ]);

        });

    });

    describe("->getPrefixesPsr4()", function () {

        it("gets prefixes", function () {

            expect($this->loader->getPrefixesPsr4())->toBe([
                'Kahlan\\' => ['src'],
                'Kahlan\\Spec\\' => ['spec']
            ]);

        });

    });

    describe("->set()", function () {

        it("sets PSR0 prefix", function () {

            $this->loader->set('Extra\\', ['extra']);

            expect($this->loader->getPrefixes())->toBe([
                'Extra\\' => ['extra']
            ]);

        });

        it("sets PSR0 fallback dir", function () {

            $this->loader->set(null, ['extra']);

            expect($this->loader->getFallbackDirs())->toBe(['extra']);

        });

    });

    describe("->add()", function () {

        it("adds PSR0 prefixes", function () {

            $this->loader->add('Extra\\', ['extra']);

            expect($this->loader->getPrefixes())->toBe([
                'Extra\\' => ['extra']
            ]);

        });

        it("appends a PSR0 prefixes", function () {

            $this->loader->add('Kahlan\\Spec\\', ['spec']);
            $this->loader->add('Kahlan\\Spec\\', ['spec2']);

            expect($this->loader->getPrefixes())->toBe([
                'Kahlan\\Spec\\' => ['spec', 'spec2']
            ]);

        });

        it("prepends a PSR0 prefixes", function () {

            $this->loader->add('Kahlan\\Spec\\', ['spec']);
            $this->loader->add('Kahlan\\Spec\\', ['spec2'], true);

            expect($this->loader->getPrefixes())->toBe([
                'Kahlan\\Spec\\' => ['spec2', 'spec']
            ]);

        });

        it("adds a PSR0 fallback dir", function () {

            $this->loader->add(null, ['extra']);
            $this->loader->add(null, ['extra2']);

            expect($this->loader->getFallbackDirs())->toBe(['extra', 'extra2']);

        });

        it("prepends a PSR0 fallback dir", function () {

            $this->loader->add(null, ['extra']);
            $this->loader->add(null, ['extra2'], true);

            expect($this->loader->getFallbackDirs())->toBe(['extra2', 'extra']);

        });

    });

    describe("->setPsr4()", function () {

        it("sets PSR4 prefixes", function () {

            $this->loader->setPsr4('Extra\\', ['extra']);

            expect($this->loader->getPrefixesPsr4())->toBe([
                'Kahlan\\' => ['src'],
                'Kahlan\\Spec\\' => ['spec'],
                'Extra\\' => ['extra']
            ]);

        });

        it("throws an exception for invalid perfixes", function () {

            $closure= function () {
                $this->loader->setPsr4('Extra', ['extra']);
            };
            expect($closure)->toThrow("A non-empty PSR-4 prefix must end with a namespace separator.");

        });

    });

    describe("->addPsr4()", function () {

        it("adds PSR4 prefixes", function () {

            $this->loader->addPsr4('Extra\\', ['extra']);

            expect($this->loader->getPrefixesPsr4())->toBe([
                'Kahlan\\' => ['src'],
                'Kahlan\\Spec\\' => ['spec'],
                'Extra\\' => ['extra']
            ]);

        });

        it("appends a PSR4 prefixes", function () {

            $this->loader->addPsr4('Kahlan\\Spec\\', ['spec2']);

            expect($this->loader->getPrefixesPsr4())->toBe([
                'Kahlan\\' => ['src'],
                'Kahlan\\Spec\\' => ['spec', 'spec2']
            ]);

        });

        it("prepends a PSR4 prefixes", function () {

            $this->loader->addPsr4('Kahlan\\Spec\\', ['spec2'], true);

            expect($this->loader->getPrefixesPsr4())->toBe([
                'Kahlan\\' => ['src'],
                'Kahlan\\Spec\\' => ['spec2', 'spec']
            ]);

        });

        it("adds a PSR4 fallback dir", function () {

            $this->loader->addPsr4(null, ['extra']);
            $this->loader->addPsr4(null, ['extra2']);

            expect($this->loader->getFallbackDirsPsr4())->toBe(['extra', 'extra2']);

        });

        it("prepends a PSR4 fallback dir", function () {

            $this->loader->addPsr4(null, ['extra']);
            $this->loader->addPsr4(null, ['extra2'], true);

            expect($this->loader->getFallbackDirsPsr4())->toBe(['extra2', 'extra']);

        });

        it("throws an exception for invalid perfixes", function () {

            $closure= function () {
                $this->loader->addPsr4('Extra', ['extra']);
            };
            expect($closure)->toThrow("A non-empty PSR-4 prefix must end with a namespace separator.");

        });

    });

    describe("->getClassMap()/->addClassMap()", function () {

        it("gets/sets class maps", function () {

            expect($this->loader->getClassMap())->toBe([]);

            $this->loader->addClassMap(['ClassA' => '/app/Class_A.php']);
            $this->loader->addClassMap(['ClassB' => '/app/Class_B.php']);

            expect($this->loader->getClassMap())->toBe([
                'ClassA' => '/app/Class_A.php',
                'ClassB' => '/app/Class_B.php'
            ]);

        });

    });

    describe("->isClassMapAuthoritative()/->setClassMapAuthoritative()", function () {

        it("gets/sets class map fallback", function () {

            expect($this->loader->isClassMapAuthoritative())->toBe(false);

            $this->loader->setClassMapAuthoritative(true);
            expect($this->loader->isClassMapAuthoritative())->toBe(true);

        });

    });

    describe("->getUseIncludePath()/->setUseIncludePath()", function () {

        it("gets/sets include path fallback", function () {

            expect($this->loader->getUseIncludePath())->toBe(false);

            $this->loader->setUseIncludePath(true);
            expect($this->loader->getUseIncludePath())->toBe(true);

        });

    });

});
