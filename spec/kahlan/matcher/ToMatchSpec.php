<?php
namespace spec\matcher;

describe("toMatch::match", function() {

	it("passes if 'Hello World!' match '/^H(?*)!$/'", function() {
		expect('Hello World!')->toMatch('/^H(.*?)!$/');
	});

});

?>