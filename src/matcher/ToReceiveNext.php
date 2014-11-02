<?php
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
