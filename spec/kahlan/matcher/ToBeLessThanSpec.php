<?php
namespace spec\matcher;

describe("toBeLessThan::match", function() {

	it("passes if 1 is < 2", function() {
		expect(1)->toBeLessThan(2);
	});

	it("passes if 0.999 < 1", function() {
		expect(0.999)->toBeLessThan(1);
	});

	it("passes if 2 is not < 2", function() {
		expect(2)->not->toBeLessThan(2);
	});

});

?>