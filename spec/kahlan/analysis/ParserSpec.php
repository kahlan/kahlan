<?php
namespace spec\kahlan\analysis;

use kahlan\analysis\Parser;

describe("Parser", function() {

    describe("->parse()", function() {

        it("parses consistently", function() {
            $sample = file_get_contents('spec/fixture/analysis/Sample.php');
            $parsed = Parser::parse($sample);
            $this->expect(Parser::unparse($parsed))->toBe($sample);
        });

    });

    describe("->debug()", function() {

        it("attaches the correct lines", function() {

            $filename = 'spec/fixture/analysis/Sample';
            $parsed = Parser::debug(file_get_contents($filename . '.php'));
            $this->expect($parsed)->toBe(file_get_contents($filename . '.txt'));

        });

        it("parses files with no namespace", function() {

            $filename = 'spec/fixture/analysis/NoNamespace';
            $parsed = Parser::debug(file_get_contents($filename . '.php'));
            $this->expect($parsed)->toBe(file_get_contents($filename . '.txt'));

        });

        it("parses heredoc", function() {

            $filename = 'spec/fixture/analysis/Heredoc';
            $parsed = Parser::debug(file_get_contents($filename . '.php'));
            $this->expect($parsed)->toBe(file_get_contents($filename . '.txt'));

        });

        it("parses strings", function() {

            $filename = 'spec/fixture/analysis/String';
            $parsed = Parser::debug(file_get_contents($filename . '.php'));
            $this->expect($parsed)->toBe(file_get_contents($filename . '.txt'));

        });

        it("parses closures", function() {

            $filename = 'spec/fixture/analysis/Closure';
            $parsed = Parser::debug(file_get_contents($filename . '.php'));
            $this->expect($parsed)->toBe(file_get_contents($filename . '.txt'));

        });

    });

});
