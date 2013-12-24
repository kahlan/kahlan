<?php
namespace spec\plugin;

use DateTime;
use kahlan\jit\Interceptor;
use kahlan\jit\Patchers;
use kahlan\plugin\Monkey;
use kahlan\analysis\Parser;
use kahlan\jit\patcher\Monkey as MonkeyPatcher;

use spec\fixture\monkey\Foo;

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

	/**
	 * Save current & reinitialize the Interceptor class.
	 */
	before(function() {
		$this->previous = Interceptor::loader();
		Interceptor::unpatch();

		$patchers = new Patchers();
		$patchers->add('monkey', new MonkeyPatcher());
		Interceptor::patch(compact('patchers'));
	});

	/**
	 * Restore Interceptor class.
	 */
	after(function() {
		Interceptor::loader($this->previous);
	});

	it("patches a core function", function() {
		$foo = new Foo();
		Monkey::patch('time', 'spec\plugin\mytime');
		expect($foo->time())->toBe(245026800);
	});

	it("patches a core function with a closure", function() {
		$foo = new Foo();
		Monkey::patch('time', function(){return 123;});
		expect($foo->time())->toBe(123);
	});

	it("patches a core class", function() {
		$foo = new Foo();
		Monkey::patch('DateTime', 'spec\plugin\MyDateTime');
		expect($foo->datetime()->getTimestamp())->toBe(245026800);
	});

	it("patches a function", function() {
		$foo = new Foo();
		Monkey::patch('spec\fixture\monkey\rand', 'spec\plugin\myrand');
		expect($foo->rand(0, 100))->toBe(101);
	});

	it("patches a class", function() {
		$foo = new Foo();
		Monkey::patch('kahlan\util\String', 'spec\plugin\MyString');
		expect($foo->hash((object)'hello'))->toBe('myhashvalue');
	});

	it("can unpatch a monkey patch", function() {
		$foo = new Foo();
		Monkey::patch('spec\fixture\monkey\rand', 'spec\plugin\myrand');
		expect($foo->rand(0, 100))->toBe(101);

		Monkey::clear('spec\fixture\monkey\rand');
		expect($foo->rand(0, 100))->toBe(50);
	});
});

?>