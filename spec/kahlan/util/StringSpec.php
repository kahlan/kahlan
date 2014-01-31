<?php
namespace spec\util;

use kahlan\util\String;

describe("String", function() {

	describe("expands", function() {

		it("expands escape sequences and escape special chars", function() {
			$dump = String::expands(" \t \nHello \0 \a \b \r\n \v \f World\n\n");
			$this->expect($dump)->toBe(' \t \nHello \0 \a \b \r\n \v \f World\n\n');
		});

	});

	describe("toString", function() {

		it("exports an array to a string dump", function() {
			$dump = String::toString(['Hello', 'World']);
			$this->expect($dump)->toBe("[\n    0 => Hello,\n    1 => World\n]");
		});

		it("exports an nested array to a string dump", function() {
			$dump = String::toString([['Hello'], ['World']]);
			$this->expect($dump)->toBe("[\n    0 => [\n        0 => Hello\n    ],\n    1 => [\n        0 => World\n    ]\n]");
		});

	});

});

?>