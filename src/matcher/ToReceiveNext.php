<?php
namespace kahlan\matcher;

class ToReceiveNext extends ToReceive
{
    /**
     * Resolves the matching.
     *
     * @return boolean Returns `true` if successfully resolved, `false` otherwise.
     */
    public function resolve()
    {
        $call = $this->_classes['call'];
        $startIndex = $call::lastFindIndex();
        $success = !!$call::find($this->_actual, $this->_message, $startIndex);
        $this->_buildDescription($startIndex);
        return $success;
    }

}
