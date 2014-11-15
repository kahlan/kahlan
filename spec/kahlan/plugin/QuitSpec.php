<?php
namespace spec\kahlan\plugin;

use kahlan\QuitException;
use kahlan\jit\Interceptor;
use kahlan\jit\Patchers;
use kahlan\plugin\Quit;
use kahlan\analysis\Parser;
use kahlan\jit\patcher\Quit as QuitPatcher;

use spec\fixture\plugin\quit\Foo;

describe("Quit", function() {

    /**
     * Save current & reinitialize the Interceptor class.
     */
    before(function() {
        $this->previous = Interceptor::loader();
        Interceptor::unpatch();

        $patchers = new Patchers();
        $patchers->add('quit', new QuitPatcher());
        Interceptor::patch(compact('patchers'));
    });

    /**
     * Restore Interceptor class.
     */
    after(function() {
        Interceptor::loader($this->previous);
    });

    describe("::disable()", function() {

        it("throws an exception when an exit statement occurs if not allowed", function() {

            Quit::disable();

            $closure = function() {
                $foo = new Foo();
                $foo->exitStatement(-1);
            };

            expect($closure)->toThrow(new QuitException('Exit statement occured', -1));

        });

    });

});
