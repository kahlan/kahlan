<?php
namespace kahlan\spec\suite\jit\patcher;

use kahlan\jit\Parser;
use kahlan\jit\patcher\Pointcut;

describe("Pointcut", function() {

    describe("->process()", function() {

        beforeEach(function() {
            $this->path = 'spec/fixture/jit/patcher/pointcut';
            $this->patcher = new Pointcut();
        });

        it("adds an entry point to methods and wrap function call for classes", function() {

            $nodes = Parser::parse(file_get_contents($this->path . '/Simple.php'));
            if (version_compare(phpversion(), '5.5', '<')) {
                $expected = file_get_contents($this->path . '/SimpleProcessed_5.4.php');
            } else {
                $expected = file_get_contents($this->path . '/SimpleProcessed.php');
            }
            $actual = Parser::unparse($this->patcher->process($nodes));
            expect($actual)->toBe($expected);

        });

        it("adds an entry point to methods and wrap function call for traits", function() {

            $nodes = Parser::parse(file_get_contents($this->path . '/SimpleTrait.php'));
            $expected = file_get_contents($this->path . '/SimpleTraitProcessed.php');
            $actual = Parser::unparse($this->patcher->process($nodes));
            expect($actual)->toBe($expected);

        });

    });

});
