<?php
namespace spec\fixture\jit\patcher\monkey;

use kahlan\util\String;

trait Filterable {

	protected function dump() {
		return String::dump('Hello');
	}

}

?>