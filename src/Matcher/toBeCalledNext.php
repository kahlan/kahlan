<?php
namespace Kahlan\Matcher;

use Kahlan\Plugin\Call\FunctionCalls;

class ToBeCalledNext extends ToBeCalled
{
    /**
     * Resolves the matching.
     *
     * @return boolean Returns `true` if successfully resolved, `false` otherwise.
     */
    public function resolve()
    {
        $startIndex = FunctionCalls::lastFindIndex();
        $report = FunctionCalls::find($this->_message, $startIndex, $this->_message->times());
        $this->_report = $report;
        $this->_buildDescription($startIndex);
        return $report['success'];
    }

}
