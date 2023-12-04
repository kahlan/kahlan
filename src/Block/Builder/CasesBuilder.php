<?php
namespace Kahlan\Block\Builder;

use Kahlan\Block\Group;
use Closure;

class CasesBuilder
{
    /**
     * The group.
     *
     * @var Group
     */
    private $_group;

    /**
     * The test cases.
     *
     * @var iterable<array-key, array>
     */
    private $_cases;

    /**
     * The timeout value.
     *
     * @var integer|null
     */
    private $_timeout;

    /**
     * The type.
     *
     * @var string
     */
    private $_type;

    /**
     * The Constructor.
     *
     * @param Group                      $group   The Group to add cases to.
     * @param iterable<array-key, array> $cases   The test cases.
     * @param integer|null               $timeout The timeout value.
     * @param string                     $type    The type.
     */
    public function __construct($group, $cases, $timeout, $type)
    {
        $this->_group = $group;
        $this->_cases = $cases;
        $this->_timeout = $timeout;
        $this->_type = $type;
    }

    /**
     * Adds a spec for each test case.
     *
     * @param string  $message Description message.
     * @param Closure $closure A test case closure.
     * @return void
     */
    public function it($message, $closure = null)
    {
        foreach ($this->_cases as $name => $args) {
            $this->_group->it(
                "{$message} {$name}",
                $closure !== null
                    ? function () use ($closure, $args) {
                        return $closure->bindTo($this)(...$args);
                    }
                    : null,
                $this->_timeout,
                $this->_type
            );
        }
    }
}
