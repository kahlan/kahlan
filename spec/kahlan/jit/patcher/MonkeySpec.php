<?php
namespace spec\jit\patcher;

use kahlan\analysis\Parser;
use kahlan\jit\patcher\Monkey;

describe("Monkey::process", function() {

	beforeEach(function() {
		$this->path = 'spec/fixture/monkey';
		$this->patcher = new Monkey();
	});

	it("adds an entry point to methods and wrap function call", function() {
		$nodes = Parser::parse(file_get_contents($this->path . '/Example.php'));
		$expected = file_get_contents($this->path . '/ExampleProcessed.php');
		$actual = Parser::unparse($this->patcher->process($nodes));
		expect($actual)->toBe($expected);
	});

});

?>