<?php
namespace Kahlan\Spec\Suite\Jit\Patcher;

use Kahlan\Jit\Parser;
use Kahlan\Jit\Patcher\Quit;

describe("Quit", function() {

    describe("->process()", function() {

        beforeEach(function() {
            $this->path = 'spec/Fixture/Jit/Patcher/Quit';
            $this->patcher = new Quit();
        });

        it("patches class's methods", function() {

            $nodes = Parser::parse(file_get_contents($this->path . '/File.php'));
            $expected = file_get_contents($this->path . '/FileProcessed.php');
            $actual = Parser::unparse($this->patcher->process($nodes));
            expect($actual)->toBe($expected);

        });

    });

});
