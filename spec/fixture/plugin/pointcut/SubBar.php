<?php
namespace kahlan\spec\fixture\plugin\pointcut;

class SubBar extends Bar
{
    use \kahlan\spec\fixture\plugin\pointcut\SubTrait;

    public function overrided()
    {
        return 'SubBar';
    }
}
