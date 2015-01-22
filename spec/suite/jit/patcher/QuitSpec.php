<?php
namespace kahlan\spec\suite\jit\patcher;

use jit\Parser;
use kahlan\jit\patcher\Quit;

describe("Quit", function() {

    describe("->process()", function() {

        beforeEach(function() {
            $this->path = 'spec/fixture/jit/patcher/quit';
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
