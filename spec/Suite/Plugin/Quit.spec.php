<?php
namespace Kahlan\Spec\Suite\Plugin;

use Kahlan\Jit\ClassLoader;
use Kahlan\QuitException;
use Kahlan\Plugin\Quit;
use Kahlan\Jit\Patcher\Quit as QuitPatcher;

use Kahlan\Spec\Fixture\Plugin\Quit\Foo;
use Kahlan\Spec\Fixture\Plugin\Pointcut\FooPhp81;

describe("Quit", function () {

    beforeAll(function () {
        $cachePath = rtrim(sys_get_temp_dir(), DS) . DS . 'kahlan';
        $include = ['Kahlan\Spec\\'];
        $this->loader = new ClassLoader();
        $this->loader->patch(compact('include', 'cachePath'));
        $this->loader->patchers()->add('quit', new QuitPatcher());
        $this->loader->addPsr4('Kahlan\\', 'src');
        $this->loader->addPsr4('Kahlan\Spec\\', 'spec');
        $this->loader->register(true);
    });

    afterAll(function () {
        $this->loader->unregister();
    });

    describe("::enable()", function () {

        it("enables quit statements", function () {

            Quit::disable();
            expect(Quit::enabled())->toBe(false);

            Quit::enable();
            expect(Quit::enabled())->toBe(true);

        });

    });

    describe("::disable()", function () {

        it("disables quit statements", function () {

            Quit::enable();
            expect(Quit::enabled())->toBe(true);

            Quit::disable();
            expect(Quit::enabled())->toBe(false);

        });

        it("throws an exception when an exit statement occurs if not allowed", function () {

            Quit::disable();

            $closure = function () {
                $foo = new Foo();
                $foo->exitStatement(-1);
            };

            expect($closure)->toThrow(new QuitException('Exit statement occurred', -1));

        });

        it("throws an exception when a die statement occurs with string message", function () {

            Quit::disable();

            $closure = function () {
                $foo = new Foo();
                $foo->dieStatement('error message');
            };

            expect($closure)->toThrow(new QuitException('Exit statement occurred with message: error message', 0));

        });

        it("throws an exception when a die statement occurs with integer code", function () {

            Quit::disable();

            $closure = function () {
                $foo = new Foo();
                $foo->dieStatement(-1);
            };

            expect($closure)->toThrow(new QuitException('Exit statement occurred', -1));

        });

        it("throws an exception with never return type", function () {

            skipIf(PHP_VERSION_ID < 80100);

            Quit::disable();
            $closure = function () {
                $foo = new FooPhp81();
                $foo->noop();
            };

            expect($closure)->toThrow(new QuitException('Exit statement occurred', 0));
            Quit::enable();

        });

    });

    describe("::reset()", function () {

        it("enables `exit()` call catching on reset", function () {

            Quit::disable();
            expect(Quit::enabled())->toBe(false);
            Quit::reset();
            expect(Quit::enabled())->toBe(true);

        });

    });

});
