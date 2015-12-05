<?php
namespace Kahlan\Spec\Suite\Jit\Patcher;

use Kahlan\Jit\Parser;
use Kahlan\Jit\Patcher\Monkey;

describe("Monkey", function() {

    describe("->process()", function() {

        beforeEach(function() {
            $this->path = 'spec/Fixture/Jit/Patcher/Monkey';
            $this->patcher = new Monkey();
        });

        it("patches class's methods", function() {

            $nodes = Parser::parse(file_get_contents($this->path . '/Class.php'));
            $expected = file_get_contents($this->path . '/ClassProcessed.php');
            $actual = Parser::unparse($this->patcher->process($nodes));
            expect($actual)->toBe($expected);

        });

        it("patches trait's methods", function() {

            $nodes = Parser::parse(file_get_contents($this->path . '/Trait.php'));
            $expected = file_get_contents($this->path . '/TraitProcessed.php');
            $actual = Parser::unparse($this->patcher->process($nodes));
            expect($actual)->toBe($expected);

        });

        it("patches plain php file", function() {

            $nodes = Parser::parse(file_get_contents($this->path . '/Plain.php'));
            $expected = file_get_contents($this->path . '/PlainProcessed.php');
            $actual = Parser::unparse($this->patcher->process($nodes));
            expect($actual)->toBe($expected);

        });

    });

});
