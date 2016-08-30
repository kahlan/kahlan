<?php
namespace Kahlan\Matcher;

use Kahlan\Plugin\Call\MethodCalls;

class ToReceiveNext extends ToReceive
{
    /**
     * Resolves the matching.
     *
     * @return boolean Returns `true` if successfully resolved, `false` otherwise.
     */
    public function resolve()
    {
        $startIndex = MethodCalls::lastFindIndex();
        $report = MethodCalls::find($this->_actual, $this->_message, $startIndex, $this->_message->times());
        $this->_report = $report;
        $this->_buildDescription($startIndex);
        return $report['success'];
    }

}
