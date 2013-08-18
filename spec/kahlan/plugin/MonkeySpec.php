<?php
namespace spec;

use kahlan\plugin\Monkey;
use kahlan\analysis\Parser;
use kahlan\jit\patcher\Monkey as MonkeyPatcher;
use kahlan\spec\fixture\monkey\Foo;
use DateTime;

function mytime() {
	return 245026800;
}

function myrand($min, $max) {
	return 101;
}

class MyDateTime {
	protected $_datetime;

	public function __construct() {
		date_default_timezone_set('UTC');
		$this->_datetime = new DateTime();
		$this->_datetime->setTimestamp(245026800);
	}

	public function __call($name, $params) {
		return call_user_func_array([$this->_datetime, $name], $params);
	}
}

class MyString {

	public static function hash($value) {
		return 'myhashvalue';
	}

}

describe("Monkey::patch", function() {

	before(function() {
		if (!class_exists('kahlan\spec\fixture\monkey\Foo')) {
			$patcher = new MonkeyPatcher();
			$file = file_get_contents('spec/fixture/monkey/Foo.php');
			eval('?>' . Parser::unparse($patcher->process(Parser::parse($file))));
		}
	});

	it("patches a core function", function() {
		$foo = new Foo();
		Monkey::patch('time', 'spec\mytime');
		expect($foo->time())->toBe(245026800);
	});

	it("patches a core function with a closure", function() {
		$foo = new Foo();
		Monkey::patch('time', function(){return 123;});
		expect($foo->time())->toBe(123);
	});

	it("patches a core class", function() {
		$foo = new Foo();
		Monkey::patch('DateTime', 'spec\MyDateTime');
		expect($foo->datetime()->getTimestamp())->toBe(245026800);
	});

	it("patches a function", function() {
		$foo = new Foo();
		Monkey::patch('kahlan\spec\fixture\monkey\rand', 'spec\myrand');
		expect($foo->rand(0, 100))->toBe(101);
	});

	it("patches a class", function() {
		$foo = new Foo();
		Monkey::patch('kahlan\util\String', 'spec\MyString');
		expect($foo->hash((object)'hello'))->toBe('myhashvalue');
	});

	it("can unpatch a monkey patch", function() {
		$foo = new Foo();
		Monkey::patch('kahlan\spec\fixture\monkey\rand', 'spec\myrand');
		expect($foo->rand(0, 100))->toBe(101);

		Monkey::clear('kahlan\spec\fixture\monkey\rand');
		expect($foo->rand(0, 100))->toBe(50);
	});
});

?>