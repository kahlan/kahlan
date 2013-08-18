<?php
namespace kahlan\spec\fixture\guard;

use kahlan\MongoId;

class Simple extends \kahlan\fixture\Parent {

	protected $_variable = true;

	public function __construct($options) {if ($__KWATCHER__ = \kahlan\plugin\Watcher::before(__METHOD__, isset($this) ? $this : get_called_class(), $args = func_get_args())) { return $__KWATCHER__($args); }

	}

	function classicMethod($param1, &$param2, &$param2 = []) {if ($__KWATCHER__ = \kahlan\plugin\Watcher::before(__METHOD__, isset($this) ? $this : get_called_class(), $args = func_get_args())) { return $__KWATCHER__($args); }
		rand(2, 5);
	}

	public function publicMethod($param1, &$param2, &$param2 = []) {if ($__KWATCHER__ = \kahlan\plugin\Watcher::before(__METHOD__, isset($this) ? $this : get_called_class(), $args = func_get_args())) { return $__KWATCHER__($args); }
		rand(2, 5);
	}

	protected function protectedMethod($param1, &$param2, &$param2 = []) {
		rand(2, 5);
	}

	private function privateMethod($param1, &$param2, &$param2 = []) {
		rand(2, 5);
	}

	final public function finalMethod($param1 = 'default', $param2 = null) {if ($__KWATCHER__ = \kahlan\plugin\Watcher::before(__METHOD__, isset($this) ? $this : get_called_class(), $args = func_get_args())) { return $__KWATCHER__($args); }
		rand(2, 5);
	}

	abstract public function abstractMethod($param1, &$param2 = array());

}

?>