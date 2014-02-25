<?php
namespace spec\util;

use kahlan\util\String;


describe("String::expands", function() {

	it("expands escape sequences and escape special chars", function() {
		$dump = String::expands(" \t \nHello \0 \a \b \r\n \v \f World\n\n");
		$this->expect($dump)->toBe(' \t \nHello \0 \a \b \r\n \v \f World\n\n');
	});

	it("expands an empty string as \"\"", function() {
		$dump = String::expands('');
		$this->expect($dump)->toBe('');
	});

	it("expands an zero string as 0", function() {
		$dump = String::expands('2014');
		$this->expect($dump)->toBe('2014');
	});

});

describe("String::toString", function() {

	it("exports an array to a string dump", function() {
		$dump = String::toString(['Hello', 'World']);
		$this->expect($dump)->toBe("[\n    0 => Hello,\n    1 => World\n]");
	});

	it("exports an nested array to a string dump", function() {
		$dump = String::toString([['Hello'], ['World']]);
		$this->expect($dump)->toBe("[\n    0 => [\n        0 => Hello\n    ],\n    1 => [\n        0 => World\n    ]\n]");
	});

});

?>