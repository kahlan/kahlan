<?php
namespace kahlan\spec\suite\plugin\call;

use kahlan\plugin\call\Message;

describe("Message", function() {

    describe("->name()", function() {

        it("Gets the message name", function() {

            $message = new Message([
                'name'    => 'message_name',
            ]);
            expect($message->name())->toBe('message_name');

        });

    });

    describe("->params()", function() {

        it('Gets the message params', function() {

            $message = new Message([
                'params'  => ['a', 'b', 'c'],
            ]);
            expect($message->params())->toBe(['a', 'b', 'c']);

        });

    });

    describe("->isStatic()", function() {

        it('Checks if the message is static', function() {

            $message = new Message([
                'static'  => true
            ]);
            expect($message->isStatic())->toBe(true);

            $message = new Message([
                'static'  => false
            ]);
            expect($message->isStatic())->toBe(false);

        });

    });

});
