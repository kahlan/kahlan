<?php
namespace Kahlan\Spec\Suite\Plugin;

use Kahlan\Jit\Interceptor;
use Kahlan\Plugin\Double;
use Kahlan\Plugin\Isolator;

describe("Isolator", function () {

    /**
     * Save current & reinitialize the Interceptor class.
     */
    beforeAll(function () {
        $this->previous = Interceptor::instance();
        Interceptor::unpatch();
    });

    /**
     * Restore Interceptor class.
     */
    afterAll(function () {
        Interceptor::load($this->previous);
    });

    describe("::isolate()", function () {

        it("adds patcher, loads file and removes patcher", function () {
            $interceptor = Double::instance();
            $patchers = Double::instance();
            Interceptor::load($interceptor);
            allow($interceptor)->toReceive('patchers')->andReturn($patchers);

            expect($patchers)->toReceive('add')->with('isolator');
            expect($interceptor)->toReceive('loadFile')->with('something');
            expect($patchers)->toReceive('remove')->with('isolator');

            Isolator::isolate('something');
        });

    });

});
