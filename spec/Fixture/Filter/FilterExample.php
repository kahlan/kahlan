<?php
namespace Kahlan\Spec\Fixture\Filter;

use Kahlan\Filter\Filters;

class FilterExample
{
    public function filterable()
    {
        return Filters::run($this, 'filterable', func_get_args(), function($next, $message) {
            return "Hello {$message}";
        });
    }
}
