<?php
namespace spec;

describe("Suite::before", function() {

	$nb = 0;

	before(function() use (&$nb) {
		$nb++;
	});

	it("passes if `before` has been executed", function() use (&$nb) {
		expect($nb)->toBe(1);
	});

	it("passes if `before` has not been executed twice", function() use (&$nb) {
		expect($nb)->toBe(1);
	});

});

describe("Suite::beforeEach", function() {

	$nb = 0;

	beforeEach(function() use (&$nb) {
		$nb++;
	});

	it("passes if `beforeEach` has been executed", function() use (&$nb) {
		expect($nb)->toBe(1);
	});

	it("passes if `beforeEach` has been executed twice", function() use (&$nb) {
		expect($nb)->toBe(2);
	});

});

describe("Suite::after", function() {

	$nb = 0;

	after(function() use (&$nb) {
		$nb++;
	});

	it("passes if `after` has not been executed", function() use (&$nb) {
		expect($nb)->toBe(0);
	});

});

describe("Suite::afterEach", function() {

	$nb = 0;

	afterEach(function() use (&$nb) {
		$nb++;
	});

	it("passes if `afterEach` has not been executed", function() use (&$nb) {
		expect($nb)->toBe(0);
	});

	it("passes if `afterEach` has been executed", function() use (&$nb) {
		expect($nb)->toBe(1);
	});

});

?>