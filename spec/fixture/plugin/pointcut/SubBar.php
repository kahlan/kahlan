<?php
namespace spec\fixture\plugin\pointcut;

class SubBar extends Bar
{
    use \spec\fixture\plugin\pointcut\SubTrait;

    public function overrided()
    {
        return 'SubBar';
    }
}
