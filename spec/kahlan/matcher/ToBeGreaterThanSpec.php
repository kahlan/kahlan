<?php
namespace spec;

describe("toBeGreaterThan::match", function() {

	it("passes if 2 is > 1", function() {
		expect(2)->toBeGreaterThan(1);
	});

	it("passes if 1 > 0.999", function() {
		expect(1)->toBeGreaterThan(0.999);
	});

	it("passes if 2 is not > 2", function() {
		expect(2)->not->toBeGreaterThan(2);
	});

});

?>