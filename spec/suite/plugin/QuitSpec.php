<?php
namespace kahlan\spec\suite\plugin;

use jit\Interceptor;
use kahlan\QuitException;
use kahlan\plugin\Quit;
use kahlan\jit\patcher\Quit as QuitPatcher;

use kahlan\spec\fixture\plugin\quit\Foo;

describe("Quit", function() {

    /**
     * Save current & reinitialize the Interceptor class.
     */
    before(function() {
        $this->previous = Interceptor::instance();
        Interceptor::unpatch();

        $cachePath = rtrim(sys_get_temp_dir(), DS) . DS . 'kahlan';
        $include = ['kahlan\spec\\'];
        $interceptor = Interceptor::patch(compact('include', 'cachePath'));
        $interceptor->patchers()->add('quit', new QuitPatcher());
    });

    /**
     * Restore Interceptor class.
     */
    after(function() {
        Interceptor::load($this->previous);
    });

    describe("::enable()", function() {

        it("enables quit statements", function() {

            Quit::disable();
            expect(Quit::enabled())->toBe(false);

            Quit::enable();
            expect(Quit::enabled())->toBe(true);

        });

    });

    describe("::disable()", function() {

        it("disables quit statements", function() {

            Quit::enable();
            expect(Quit::enabled())->toBe(true);

            Quit::disable();
            expect(Quit::enabled())->toBe(false);

        });

    });

    describe("::disable()", function() {

        it("throws an exception when an exit statement occurs if not allowed", function() {

            Quit::disable();

            $closure = function() {
                $foo = new Foo();
                $foo->exitStatement(-1);
            };

            expect($closure)->toThrow(new QuitException('Exit statement occurred', -1));

        });

    });

});
