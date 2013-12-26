<?php
namespace spec\matcher;

use kahlan\jit\Interceptor;
use kahlan\jit\Patchers;
use kahlan\jit\patcher\Pointcut;
use kahlan\analysis\Parser;

use spec\fixture\plugin\pointcut\Foo;

describe("toReceiveNext::match", function() {

	/**
	 * Save current & reinitialize the Interceptor class.
	 */
	before(function() {
		$this->previous = Interceptor::loader();
		Interceptor::unpatch();

		$patchers = new Patchers();
		$patchers->add('pointcut', new Pointcut());
		Interceptor::patch(compact('patchers'));
	});

	/**
	 * Restore Interceptor class.
	 */
	after(function() {
		Interceptor::loader($this->previous);
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