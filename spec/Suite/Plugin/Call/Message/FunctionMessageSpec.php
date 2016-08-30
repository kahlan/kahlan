<?php
namespace Kahlan\Spec\Suite\Plugin\Call\Message;

use Kahlan\Plugin\Call\Message\FunctionMessage;

describe("FunctionMessage", function() {

    describe("->name()", function() {

        it("Gets the message name", function() {

            $message = new FunctionMessage([
                'name'    => 'function_name',
            ]);
            expect($message->name())->toBe('function_name');

        });

    });

    describe("->params()", function() {

        it('Gets the message params', function() {

            $message = new FunctionMessage([
                'params'  => ['a', 'b', 'c'],
            ]);
            expect($message->params())->toBe(['a', 'b', 'c']);

        });

    });

});
