<?php
/**
 * Kahlan: PHP Testing Framework
 *
 * @copyright     Copyright 2013, CrysaLEAD
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace kahlan\matcher;

class ToReceiveNext extends ToReceive {

	public function resolve() {
		$call = $this->_classes['call'];
		return $call::find($this->_actual, $this->_message, false);
	}
}

?>