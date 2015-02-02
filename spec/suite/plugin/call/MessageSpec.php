<?php
namespace kahlan\spec\suite\plugin\call;

use kahlan\plugin\call\Message;

describe("Message", function() {

    it('->name()', function() {

        $message = new Message([
            'name'    => 'message_name',
        ]);
        expect($message->name())->toBe('message_name');

    });

    it('->params()', function() {

        $message = new Message([
            'params'  => ['a', 'b', 'c'],
        ]);
        expect($message->params())->toBe(['a', 'b', 'c']);

    });

    it('->isStatic()', function() {

        $message = new Message([
            'static'  => true
        ]);
        expect($message->isStatic())->toBe(true);

    });

});
