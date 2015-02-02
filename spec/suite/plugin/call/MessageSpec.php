<?php

namespace kahlan\spec\suite\plugin\call;

use kahlan\plugin\call\Message;

describe("Message", function() {

    it('->getName()', function() {

        $message = new Message([
            'name'    => 'message_name',
        ]);
        expect($message->getName())->toBe('message_name');

    });

    it('->getWith()', function() {

        $message = new Message([
            'params'  => ['a', 'b', 'c'],
        ]);
        expect($message->getWith())->toBe(['a', 'b', 'c']);

    });

    it('->getStatic()', function() {

        $message = new Message([
            'static'  => 'static'
        ]);
        expect($message->getStatic())->toBe('static');

    });

});
