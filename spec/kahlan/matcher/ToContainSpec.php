<?php
namespace spec\matcher;

describe("toContain::match", function() {

    it("passes if 3 is in [1, 2, 3]", function() {
        expect([1, 2, 3])->toContain(3);
    });

    it("passes if 'a' is in ['a', 'b', 'c']", function() {
        expect(['a', 'b', 'c'])->toContain('a');
    });

    it("passes if 'd' is in ['a', 'b', 'c']", function() {
        expect(['a', 'b', 'c'])->not->toContain('d');
    });

});

?>