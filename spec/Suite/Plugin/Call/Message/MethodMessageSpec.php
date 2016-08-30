<?php
namespace Kahlan\Spec\Suite\Plugin\Call\Message;

use Kahlan\Plugin\Call\Message\MethodMessage;

describe("MethodMessage", function() {

    describe("->name()", function() {

        it("Gets the message name", function() {

            $message = new MethodMessage([
                'name'    => 'message_name',
            ]);
            expect($message->name())->toBe('message_name');

        });

    });

    describe("->params()", function() {

        it('Gets the message params', function() {

            $message = new MethodMessage([
                'params'  => ['a', 'b', 'c'],
            ]);
            expect($message->params())->toBe(['a', 'b', 'c']);

        });

    });

    describe("->isStatic()", function() {

        it('Checks if the message is static', function() {

            $message = new MethodMessage([
                'static'  => true
            ]);
            expect($message->isStatic())->toBe(true);

            $message = new MethodMessage([
                'static'  => false
            ]);
            expect($message->isStatic())->toBe(false);

        });

    });

});
