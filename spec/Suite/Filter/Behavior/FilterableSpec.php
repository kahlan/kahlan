<?php
namespace Kahlan\Spec\Suite\Filter\Behavior;

use Kahlan\Plugin\Stub;
use Kahlan\Filter\MethodFilters;

describe('Filterable', function() {

	beforeEach(function() {
		$this->mock = Stub::create(['uses' => ['Kahlan\Filter\Behavior\Filterable']]);

		Stub::on($this->mock)->method('filterable', function() {
			return Filter::on($this, 'filterable', func_get_args(), function($chain, $message) {
				return "Hello {$message}";
			});
		});
	});

	describe("methodFilters", function() {

		it("gets the `MethodFilters` instance", function() {

			expect($this->mock->methodFilters())->toBeAnInstanceOf('Kahlan\Filter\MethodFilters');

		});

		it("sets a new `MethodFilters` instance", function() {

			$methodFilters = new MethodFilters();
			expect($this->mock->methodFilters($methodFilters))->toBeAnInstanceOf('Kahlan\Filter\MethodFilters');
			expect($this->mock->methodFilters())->toBe($methodFilters);

		});

	});

});
