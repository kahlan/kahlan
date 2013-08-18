<?php
namespace spec;

use kahlan\analysis\Parser;
use kahlan\jit\patcher\Watcher;
use kahlan\spec\fixture\watcher\Foo;

describe("toReceiveNext::match", function() {

	before(function() {
		if (!class_exists('kahlan\spec\fixture\watcher\Foo', false)) {
			$patcher = new Watcher();
			$file = file_get_contents('spec/fixture/watcher/Foo.php');
			eval('?>' . Parser::unparse($patcher->process(Parser::parse($file))));
		}
	});

	it("expects called methods to be called in a defined order", function() {
		$foo = new Foo();
		expect($foo)->toReceive('message');
		expect($foo)->toReceiveNext('::version');
		expect($foo)->toReceiveNext('bar');
		$foo->message();
		$foo::version();
		$foo->bar();
	});

	it("expects called methods to not be called in a different order", function() {
		$foo = new Foo();
		expect($foo)->toReceive('message');
		expect($foo)->not->toReceiveNext('bar');
		$foo->bar();
		$foo->message();
	});

});

?>