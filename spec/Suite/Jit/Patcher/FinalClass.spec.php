<?php
namespace Kahlan\Spec\Suite\Jit\Patcher;

use Kahlan\Dir\Dir;
use Kahlan\Jit\ClassLoader;
use Kahlan\Jit\Parser;
use Kahlan\Jit\Patcher\FinalClass;
use Kahlan\Plugin\Double;

describe("FinalClass", function () {

    beforeEach(function () {
        class_exists('Kahlan\Plugin\Pointcut');
        class_exists('Kahlan\Plugin\Call\Calls');
        class_exists('Kahlan\Jit\Parser');
        class_exists('Kahlan\Jit\JitException');
        class_exists('Kahlan\Jit\Node\BlockDef');
        class_exists('Kahlan\Jit\Node\FunctionDef');
        class_exists('Kahlan\Jit\TokenStream');

        $this->cachePath = Dir::tempnam(null, 'cache');
        $this->loader = new ClassLoader();
        $this->loader->addPsr4('FakeNamespace\\', 'spec/Fixture/Jit/Patcher/FinalClass');
        $this->loader->patch([
            'include'    => ['FakeNamespace\\'],
            'cachePath'  => $this->cachePath,
        ]);
        $this->patcher = new FinalClass();
        $patchers = $this->loader->patchers();
        $patchers->add('final', $this->patcher);
        $this->loader->register(true);
    });

    afterEach(function () {
        $this->loader->unpatch();
        $this->loader->unregister();
        if (file_exists($this->cachePath)) {
            Dir::remove($this->cachePath);
        }
    });

    describe("->process()", function () {

        it("must remove final keyword on classes", function () {
            $this->path = 'spec/Fixture/Jit/Patcher/FinalClass';
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

    describe("with a final class", function () {

        it("must allow a double creation and usage", function () {

            $double = Double::instance([
                'extends' => 'FakeNamespace\FinalClass',
            ]);

            expect($double->hello())->toBe('Hello World');

            expect($double)->toBeAnInstanceOf('FakeNamespace\FinalClass');

        });

    });

});
