<?php
namespace spec\jit\patcher;

use kahlan\IncompleteException;
use kahlan\jit\Patchers;
use kahlan\jit\Interceptor;
use kahlan\jit\patcher\Substitute;

describe("Substitute::create", function() {

	/**
	 * Save current & reinitialize the Interceptor class.
	 */
	before(function() {
		$this->previous = Interceptor::loader();
		Interceptor::unpatch();

		$patchers = new Patchers();
		$patchers->add('substitute', new Substitute(['namespaces' => ['spec\\']]));
		Interceptor::patch(compact('patchers'));
	});

	/**
	 * Restore Interceptor class.
	 */
	after(function() {
		Interceptor::loader($this->previous);
	});

	it("throws an IncompleteException when creating an unexisting class", function() {
		$closure = function() {
			$mock = new Abcd();
			$mock->helloWorld();
		};
		expect($closure)->toThrow(new IncompleteException);
	});

	it("allows magic call static on live mock", function() {
		expect(function(){ Abcd::helloWorld(); })->toThrow(new IncompleteException);
	});

});

?>