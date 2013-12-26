<?php
namespace spec\fixture\jit\patcher\monkey;

use kahlan\util\String;

trait Filterable {

	protected function dump() {$__KMONKEY__0 = \kahlan\plugin\Monkey::patched(null, 'kahlan\util\String');
		return $__KMONKEY__0::dump('Hello');
	}

}

?>