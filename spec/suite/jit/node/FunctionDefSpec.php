<?php
namespace kahlan\spec\suite\jit\node;

use kahlan\jit\node\FunctionDef;

describe("FunctionDef", function() {

    describe("->argsToParams()", function() {

        it("builds a list of params from function arguments", function() {
            $node = new FunctionDef();
            $node->args = [
                '$required',
                '$param'    => '"value"',
                '$boolean'  => 'false',
                '$array'    => '[]',
                '$array2'   => 'array()',
                '$constant' => 'PI'
            ];
            expect($node->argsToParams())->toBe('$required, $param, $boolean, $array, $array2, $constant');
        });

    });

});
