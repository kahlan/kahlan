<?php
namespace spec\analysis;

use kahlan\analysis\Parser;

describe("Parser", function() {

    beforeEach(function() {
        $this->sample = file_get_contents('spec/fixture/analysis/Sample.php');
        $this->sampleTxt = file_get_contents('spec/fixture/analysis/Sample.txt');
        $this->noNamespace = file_get_contents('spec/fixture/analysis/NoNamespace.php');
        $this->noNamespaceTxt = file_get_contents('spec/fixture/analysis/NoNamespace.txt');
    });

    describe("->parse()", function() {

        it("parses consistently", function() {
            $parsed = Parser::parse($this->sample);
            $this->expect(Parser::unparse($parsed))->toBe($this->sample);
        });

    });

    describe("->debug()", function() {

        it("attaches the correct lines", function() {
            $parsed = Parser::debug($this->sample);
            $this->expect($parsed)->toBe($this->sampleTxt);
        });

        it("parses files with no namespace", function() {
            $parsed = Parser::debug($this->noNamespace);
            $this->expect($parsed)->toBe($this->noNamespaceTxt);
        });

    });

});
