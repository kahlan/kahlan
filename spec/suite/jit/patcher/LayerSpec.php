<?php
namespace kahlan\spec\suite\jit\patcher;

use jit\Parser;
use kahlan\jit\patcher\Layer;

describe("Layer", function() {

    describe("->process()", function() {

        beforeEach(function() {
            $this->path = 'spec/fixture/jit/patcher/layer';
            $this->patcher = new Layer([
                'override' => [
                    'kahlan\analysis\Inspector'
                ]
            ]);
        });

        it("patches class's extends", function() {

            $nodes = Parser::parse(file_get_contents($this->path . '/Layer.php'));
            $actual = Parser::unparse($this->patcher->process($nodes));
            expect($actual)->toMatch('~Inspector extends \\\kahlan\\\spec\\\plugin\\\stub\\\Stub\d+~');

        });

    });

});
