<?php
namespace kahlan\spec\fixture\watcher;

class Bar {
	public function send() {
		return 'success';
	}
}

class Foo {

	protected $_classes = [
		'bar' => 'kahlan\spec\fixture\watcher\Bar'
	];

	protected $_inited = false;

	protected $_status = 'none';

	protected $_message = 'Hello World!';

	public function __construct() {
		$this->_inited = true;
	}

	public function inited() {
		return $this->_inited;
	}

	public function message($message = null) {
		if ($message === null) {
			return $this->_message;
		}
		$this->_message = $message;
	}

	public function bar() {
		$bar = $this->_classes['bar'];
		$bar = new $bar();
		return $bar->send();
	}

	public function __call($name, $params) {
	}

	public static function __callStatic($name, $params) {
	}

	public static function version() {
		return '0.0.8b';
	}
}

?>