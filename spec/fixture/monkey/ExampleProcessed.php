<?php
namespace spec\fixture\monkey;

use kahlan\MongoId;
use kahlan\util\String;

function time() {
	return 0;
}

class Example extends \kahlan\fixture\Parent {

	use A, B {
		B::smallTalk insteadof A;
		A::bigTalk insteadof B;
	}

	public function classic() {$__KMONKEY__0 = \kahlan\plugin\Monkey::patched(__NAMESPACE__ , 'rand', true);
		$__KMONKEY__0(2, 5);
	}

	public function rootBased() {$__KMONKEY__1 = \kahlan\plugin\Monkey::patched(null , 'rand');
		$__KMONKEY__1(2, 5);
	}

	public function nested() {$__KMONKEY__2 = \kahlan\plugin\Monkey::patched(__NAMESPACE__ , 'rand', true);$__KMONKEY__3 = \kahlan\plugin\Monkey::patched(__NAMESPACE__ , 'rand', true);$__KMONKEY__4 = \kahlan\plugin\Monkey::patched(__NAMESPACE__ , 'rand', true);
		return $__KMONKEY__2($__KMONKEY__3(2, 5), $__KMONKEY__4(6, 10));
	}

	public function inString() {
		'rand(2, 5)';
	}

	public function namespaced() {$__KMONKEY__5 = \kahlan\plugin\Monkey::patched(__NAMESPACE__ , 'time', true);
		$__KMONKEY__5();
	}

	public function rootBasedInsteadOfNamespaced() {$__KMONKEY__6 = \kahlan\plugin\Monkey::patched(null , 'time');
		$__KMONKEY__6();
	}

	public function instantiate() {$__KMONKEY__7 = \kahlan\plugin\Monkey::patched(__NAMESPACE__ , 'stdClass', false);
		new $__KMONKEY__7;
	}

	public function instantiateRootBased() {$__KMONKEY__8 = \kahlan\plugin\Monkey::patched(null , 'stdClass');
		new $__KMONKEY__8;
	}

	public function instantiateFromUsed() {$__KMONKEY__9 = \kahlan\plugin\Monkey::patched(null, 'kahlan\MongoId');
		new $__KMONKEY__9;
	}

	public function instantiateRootBasedFromUsed() {$__KMONKEY__10 = \kahlan\plugin\Monkey::patched(null , 'MongoId');
		new $__KMONKEY__10;
	}

	public function instantiateVariable() {
		$class = 'MongoId';
		new $class;
	}

	public function staticCall() {$__KMONKEY__11 = \kahlan\plugin\Monkey::patched(__NAMESPACE__ , 'Debugger', false);
		return $__KMONKEY__11::trace();
	}

	public function staticCallFromUsed() {$__KMONKEY__12 = \kahlan\plugin\Monkey::patched(null, 'kahlan\util\String');
		return $__KMONKEY__12::hash((object) 'hello');
	}

	public function noIndent() {$__KMONKEY__13 = \kahlan\plugin\Monkey::patched(__NAMESPACE__ , 'rand', true);
$__KMONKEY__13();
	}

	public function closure() {
		$func = function() {$__KMONKEY__14 = \kahlan\plugin\Monkey::patched(__NAMESPACE__ , 'rand', true);
			$__KMONKEY__14(2.5);
		};
		$func();
	}

	public function ignoreControlStructure() {
		array();
		try{} catch (\Exception $e) {};
		compact();
		declare(ticks=1);
		die();
		echo('');
		empty($a);
		eval();
		exit(-1);
		extract();
		for($i=0;$i<1;$i++) {};
		foreach($array as $key=>$value) {}
		function(){};
		if(true){}
		include('filename');
		include_once('filename');
		if(false){} elseif(true) {}
		isset($a);
		list($a, $b) = ['A', 'B'];
		new self();
		new static();
		parent::hello();
		print('hello');
		require('filename');
		require_once('filename');
		return($a);
		switch($case){}
		unset($a);
		while(false){}
	}
}

?>