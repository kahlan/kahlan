<?php
namespace Kahlan\Spec\Suite\Jit\Patcher;

use Kahlan\Jit\Parser;
use Kahlan\Jit\Patcher\Isolator;

describe("Isolator", function () {

    beforeAll(function () {
        $this->empty = "<?php\n";
    });

    beforeEach(function () {
        $this->path = 'spec/Fixture/Jit/Patcher/Isolator';
        $this->patcher = new Isolator();
    });

    describe("->process()", function () {

        it("keeps open PHP tag", function () {
            $nodes = Parser::parse('<?php ');  // Extra space is parser workaround
            $result = Parser::unparse($this->patcher->process($nodes));
            expect($result)->toBe('<?php ');
        });

        it("keeps functions", function () {
            $nodes = Parser::parse(
                file_get_contents($this->path.'/function.php')
            );
            $expected = file_get_contents($this->path.'/functionProcessed.php');
            $actual = Parser::unparse($this->patcher->process($nodes));
            expect($actual)->toBe($expected);
        });

        it("removes PHP code outside of functions", function () {
            $nodes = Parser::parse(
                file_get_contents($this->path.'/code.php')
            );
            $actual = Parser::unparse($this->patcher->process($nodes));
            expect($actual)->toBe($this->empty);
        });

        it("removes classes", function () {
            $nodes = Parser::parse(
                file_get_contents($this->path.'/class.php')
            );
            $actual = Parser::unparse($this->patcher->process($nodes));
            expect($actual)->toBe($this->empty);
        });

        it("supports nested functions", function () {
            $nodes = Parser::parse(
                file_get_contents($this->path.'/nested.php')
            );
            $expected = file_get_contents($this->path.'/nestedProcessed.php');
            $actual = Parser::unparse($this->patcher->process($nodes));
            expect($actual)->toBe($expected);
        });

        it("keeps 'use' statements", function () {
            $nodes = Parser::parse(file_get_contents($this->path.'/use.php'));
            $expected = file_get_contents($this->path.'/useProcessed.php');
            $actual = Parser::unparse($this->patcher->process($nodes));
            expect($actual)->toBe($expected);
        });

    });

    describe("->patchable()", function () {

        it("returns `true`", function () {
            expect($this->patcher->patchable('SomeClass'))->toBe(true);
        });

    });

    describe("->findFile()", function () {

        it("returns file name AS IS", function () {
            expect($this->patcher->findFile('', '', 'path'))->toBe('path');
        });

    });

});
