<?php
/**
 * Kahlan: PHP Testing Framework
 *
 * @copyright     Copyright 2013, CrysaLEAD
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace kahlan\matcher;

class ToReceiveNext extends ToReceive
{
    public function resolve($report)
    {
        $call = $this->_classes['call'];
        $startIndex = $call::lastFindIndex();
        $success = !!$call::find($this->_actual, $this->_message, $startIndex);
        if (!$success) {
            $this->report($report, $startIndex);
        }
        return $success;
    }
}
