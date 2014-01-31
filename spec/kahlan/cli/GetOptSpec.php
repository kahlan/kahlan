<?php
namespace spec\cli;

use kahlan\cli\GetOpt;

describe("GetOpt::parse", function() {

	it("parses command line options", function() {
		$actual = GetOpt::parse([
			'command', '--option1', '--option3=value3', '--', '--ingored'
		]);
		expect($actual)->toEqual([
			'option1' => '',
			'option3' => 'value3'
		]);
	});

	it("provides an array when some multiple occurences of a same option are present", function() {
		$actual = GetOpt::parse([
			'command', '--option1', '--option1=value1' , '--option1=value2'
		]);
		expect($actual)->toEqual([
			'option1' => [
				'',
				'value1',
				'value2'
			]
		]);
	});

	it("allows bool casting", function() {
		$actual = GetOpt::parse([
			'command', '--option1', '--option2=true' , '--option3=false'
		], [
			'option1' => 'bool',
			'option2' => 'bool',
			'option3' => 'bool'
		]);
		expect($actual)->toEqual([
			'option1' => true,
			'option2' => true,
			'option3' => false
		]);
	});

	it("allows integer casting", function() {
		$actual = GetOpt::parse([
			'command', '--option', '--option0=0', '--option1=1', '--option2=2'
		], [
			'option' => 'numeric',
			'option0' => 'numeric',
			'option1' => 'numeric',
			'option2' => 'numeric'
		]);
		expect($actual)->toEqual([
			'option' => 1,
			'option0' => 0,
			'option1' => 1,
			'option2' => 2
		]);
	});

});

?>