<?php
$root = $this->suite()->root();

$root->describe("Fail", function() {

	$this->it("fail", function() {

		$this->expect(true)->toBe(false);

	});

});
