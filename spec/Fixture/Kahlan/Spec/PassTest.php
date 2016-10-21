<?php
$root = $this->suite()->root();

$root->describe("Pass", function() {

	$this->it("pass", function() {

		$this->expect(true)->toBe(true);

	});

});
