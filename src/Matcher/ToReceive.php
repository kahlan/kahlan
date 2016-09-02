<?php
namespace Kahlan\Matcher;

use Kahlan\Suite;
use Kahlan\Analysis\Debugger;
use Kahlan\Analysis\Inspector;
use Kahlan\Plugin\Call\Message;
use Kahlan\Plugin\Call\Calls;
use Kahlan\Plugin\Stub;
use Kahlan\Plugin\Monkey;

class ToReceive
{
    /**
     * The messages instance.
     *
     * @var object
     */
    protected $_messages = [];

    /**
     * The expected method method name to be called.
     *
     * @var array
     */
    protected $_expected = [];

    /**
     * The expectation backtrace reference.
     *
     * @var array
     */
    protected $_backtrace = null;

    /**
     * The report.
     *
     * @var array
     */
    protected $_report = [];

    /**
     * The description report.
     *
     * @var array
     */
    protected $_description = [];

    /**
     * Number of occurences to match.
     *
     * @var integer
     */
    protected $_times = 0;

    /**
     * If `true`, will take the calling order into account.
     *
     * @var boolean
     */
    protected $_ordered = false;

    /**
     * Checks that `$actual` receive the `$expected` message.
     *
     * @param  mixed   $actual   The actual value.
     * @param  mixed   $expected The expected message.
     * @return boolean
     */
    public static function match($actual, $expected)
    {
        $class = get_called_class();
        return new static($actual, $expected);
    }

    /**
     * Constructor
     *
     * @param string|object $actual   A fully-namespaced class name or an object instance.
     * @param string        $expected The expected method method name to be called.
     */
    public function __construct($actual, $expected)
    {
        if (preg_match('/^::.*/', $expected)) {
            $actual = is_object($actual) ? get_class($actual) : $actual;
        }

        $parts = preg_split('~((?:->|::)[^-:]+)~', $expected, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

        $expected = [];
        foreach ($parts as $name) {
            $expected[] = isset($name[0]) && $name[0] === '-' ? substr($name, 2) : $name;
        }

        $this->_expected = $expected;

        $total = count($expected);

        $actual = $this->_actual($actual);

        foreach ($expected as $index => $method) {
            $this->_messages[] = $this->_message($actual, $method);
            if ($index < $total - 1) {
                $stub = Stub::create();
                Stub::on($actual)->method($method)->andReturn($stub);
                $actual = $stub;
            }
        }

        $this->_backtrace = Debugger::backtrace();
    }

    /**
     * Replaces core classes using a dynamic stub when possible.
     *
     * @param  string|object $actual A fully-namespaced class name or an object instance.
     * @return string                The actual value to use.
     */
    protected function _actual($actual)
    {
        if (!is_string($actual) || !class_exists($actual)) {
            return $actual;
        }
        $reflection = Inspector::inspect($actual);

        if (!$reflection->isInternal()) {
            return $actual;
        }
        $layer = Stub::classname();
        Monkey::patch($actual, $layer);

        return $layer;
    }

    /**
     * Build a message instance and make it watched.
     *
     * @param string|object $actual A fully-namespaced class name or an object instance.
     * @param string        $method The expected method method name to be called.
     * @param object                A message instance.
     */
    protected function _message($actual, $method)
    {
        if (is_object($actual)) {
            Suite::register(get_class($actual));
        }
        Suite::register(Suite::hash($actual));
        return new Message([
            'reference' => $actual,
            'name'      => $method
        ]);
    }

    /**
     * Sets arguments requirement.
     *
     * @param  mixed ... <0,n> Argument(s).
     * @return self
     */
    public function with()
    {
        $message = end($this->_messages);
        call_user_func_array([$message, 'with'], func_get_args());
        return $this;
    }

    /**
     * Sets the number of occurences.
     *
     * @return self
     */
    public function once()
    {
        $this->times(1);
        return $this;
    }

    /**
     * Gets/sets the number of occurences.
     *
     * @param  integer $times The number of occurences to set or none to get it.
     * @return mixed          The number of occurences on get or `self` otherwise.
     */
    public function times($times = null)
    {
        if (!func_num_args()) {
            return $this->_times;
        }
        $this->_times = $times;
        return $this;
    }

    /**
     * Sets the stub logic.
     *
     * @param Closure $closure The logic.
     */
    public function andRun($closure)
    {
        $message = end($this->_messages);
        $reference = $message->reference();
        $method = $message->name();
        Stub::on($reference)->method($method, $closure);
    }

    /**
     * Set. return values.
     *
     * @param mixed ... <0,n> Return value(s).
     */
    public function andReturn()
    {
        $message = end($this->_messages);
        $reference = $message->reference();
        $method = $message->name();
        $stub = Stub::on($reference)->method($method);
        call_user_func_array([$stub, 'andReturn'], func_get_args());
    }

    /**
     * Magic getter, if called with `'ordered'` will set ordered to `true`.
     *
     * @param string
     */
    public function __get($name)
    {
        if ($name !== 'ordered') {
            throw new Exception("Unsupported attribute `{$name}`.");
        }
        $this->_ordered = true;
        return $this;
    }

    /**
     * Resolves the matching.
     *
     * @return boolean Returns `true` if successfully resolved, `false` otherwise.
     */
    public function resolve()
    {
        $startIndex = $this->_ordered ? Calls::lastFindIndex() : 0;
        $report = Calls::find($this->_messages, $startIndex, $this->times());
        $this->_report = $report;
        $this->_buildDescription($startIndex);
        return $report['success'];
    }

    /**
     * Gets the backtrace reference.
     *
     * @return object
     */
    public function backtrace()
    {
        return $this->_backtrace;
    }

    /**
     * Build the description of the runned `::match()` call.
     *
     * @param mixed $startIndex The startIndex in calls log.
     */
    public function _buildDescription($startIndex = 0)
    {
        $times = $this->times();

        $report = $this->_report;
        $reference = $report['message']->reference();
        $expected = $report['message']->name();
        $with = $report['message']->args();


        $expectedTimes = $times ? ' the expected times' : '';
        $expectedParameters = $with ? ' with expected parameters' : '';

        $this->_description['description'] = "receive the expected method{$expectedParameters}{$expectedTimes}.";

        $calledTimes = count($report['args']);

        if (!$calledTimes) {
            $logged = [];
            foreach(Calls::logs($reference, $startIndex) as $log) {
                $logged[] = $log['static'] ? '::' . $log['name'] : $log['name'];
            }
            $this->_description['data']['actual received calls'] = $logged;
        } elseif ($calledTimes) {
            $this->_description['data']['actual received'] = $expected;
            $this->_description['data']['actual received times'] = $calledTimes;
            if ($with !== null) {
               $this->_description['data']['actual received parameters list'] = $report['args'];
            }
        }

        $this->_description['data']['expected to receive'] = $expected;

        if ($with !== null) {
            $this->_description['data']['expected parameters'] = $with;
        }

        if ($times) {
            $this->_description['data']['expected received times'] = $times;
        }
    }

    /**
     * Returns the description report.
     */
    public function description()
    {
        return $this->_description;
    }

}
