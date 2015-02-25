<?php
namespace kahlan\spec\fixture\reporter\coverage;

class ImplementsCoverage implements ImplementsCoverageInterface
{
    public function foo($a = null)
    {
        return $a;
    }
}