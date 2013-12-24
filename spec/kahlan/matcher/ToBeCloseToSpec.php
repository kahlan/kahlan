<?php
namespace spec\matcher;

describe("toBeCloseTo::match", function() {

	it("passes if the difference is lower than the default two decimal", function() {
		expect(0)->toBeCloseTo(0.001);
	});

	it("fails if the difference is higher than the default two decimal", function() {
		expect(0)->not->toBeCloseTo(0.01);
	});

	it("passes if the difference is lower than the precision", function() {
		expect(0)->toBeCloseTo(0.01, 1);
	});

	it("fails if the difference is higher than the precision", function() {
		expect(0)->not->toBeCloseTo(0.1, 1);
	});

	it("passes if the difference with the round is lower than the default two decimal", function() {
		expect(1.23)->toBeCloseTo(1.225);

		expect(1.23)->toBeCloseTo(1.234);
	});

	it("fails if the difference with the round is lower than the default two decimal", function() {
		expect(1.23)->not->toBeCloseTo(1.2249999);
	});

});

?>