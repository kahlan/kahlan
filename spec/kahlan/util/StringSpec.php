<?php

namespace spec;

use kahlan\util\String;

describe("String", function() {

	describe("expands", function() {

		it("expands escape sequences and escape special chars", function() {
			$dump = String::expands(" \t \nHello \0 \a \b \r\n \v \f World\n\n");
			$this->expect($dump)->toBe(' \t \nHello \0 \a \b \r\n \v \f World\n\n');
		});

	});

});

?>