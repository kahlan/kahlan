<?php
namespace spec;

use stdClass;

describe("toBeAnInstanceOf::match", function() {

	it("passes if an instance of stdClass is an object", function() {
		expect(new stdClass())->toBeAnInstanceOf('stdClass');
	});

	it("passes if an instance of stdClass is not a Exception", function() {
		expect(new stdClass())->not->toBeAnInstanceOf('Exception');
	});
});

?>