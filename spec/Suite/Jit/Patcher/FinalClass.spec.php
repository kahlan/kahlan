<?php

namespace Kahlan\Spec\Suite\Jit\Patcher;

use Kahlan\Jit\Parser;
use Kahlan\Jit\Patcher\FinalClass;
use Kahlan\Plugin\Double;

describe("FinalClass", function () {

    beforeEach(function () {
        $this->path = 'spec/Fixture/Jit/Patcher/FinalClass';
        $this->patcher = new FinalClass();
    });

    describe("->process()", function () {

        it("must remove final keyword on classes", function () {

            $nodes = Parser::parse(file_get_contents($this->path . '/FinalClass.php'));
            $expected = file_get_contents($this->path . '/FinalClassProcessed.php');
            $actual = Parser::unparse($this->patcher->process($nodes));
            expect($actual)->toBe($expected);

        });

    });

    describe("->patchable()", function () {

        it("return `true`", function () {

            expect($this->patcher->patchable('SomeClass'))->toBe(true);

        });

    });

    describe("On a final class", function () {

        it("must allow a double creation and usage", function () {

            $double = Double::instance([
                'class' => \FinalClass::class,
            ]);

            allow($double)->toReceive('test')->andReturn(true);

            expect($double)->toBeAnInstanceOf(\FinalClass::class);

        });

    });
});
