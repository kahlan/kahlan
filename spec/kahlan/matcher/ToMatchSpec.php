<?php
namespace spec;

describe("toMatch::match", function() {

	it("passes if 'Hello World!' match '/^H(?*)!$/'", function() {
		expect('Hello World!')->toMatch('/^H(.*?)!$/');
	});

});

?>