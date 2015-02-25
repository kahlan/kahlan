<?php
namespace kahlan\spec\fixture\reporter\coverage;

class NoEmptyLine
{
    public function shallNotPass()
    {
        $shallNotPass = false;
        if (false) {
            $shallNotPass = true;
        }
        return $shallNotPass;
        $shallNotPass = true;
    }
}