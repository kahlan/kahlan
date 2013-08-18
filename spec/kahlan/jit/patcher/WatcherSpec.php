<?php
namespace spec;

use kahlan\analysis\Parser;
use kahlan\jit\patcher\Watcher;

describe("Watcher::process", function() {

	beforeEach(function() {
		$this->path = 'spec/fixture/watcher';
		$this->patcher = new Watcher();
	});

	it("adds an entry point to methods and wrap function call", function() {
		$nodes = Parser::parse(file_get_contents($this->path . '/Simple.php'));
		$expected = file_get_contents($this->path . '/SimpleProcessed.php');
		$actual = Parser::unparse($this->patcher->process($nodes));
		expect($actual)->toBe($expected);
	});

});

?>