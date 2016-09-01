<?php
namespace Kahlan\Matcher;

use Kahlan\Analysis\Debugger;
use Kahlan\Plugin\Call\MethodCalls;

class ToReceive
{
    /**
     * A fully-namespaced class name or an object instance.
     *
     * @var string|object
     */
    protected $_actual = null;

    /**
     * The expected method method name to be called.
     *
     * @var string
     */
    protected $_expected = null;

    /**
     * The expectation backtrace reference.
     *
     * @var array
     */
    protected $_backtrace = null;

    /**
     * The call instance.
     *
     * @var object
     */
    protected $_calls = null;

    /**
     * The message instance.
     *
     * @var object
     */
    protected $_message = null;

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

        $this->_actual    = $actual;
        $this->_expected  = $expected;
        $this->_calls      = new MethodCalls($actual);
        $this->_message   = $this->_calls->method($expected);
        $this->_backtrace = Debugger::backtrace();
    }

    /**
     * Delegates calls to the message instance.
     *
     * @param  string $method The method name.
     * @param  array  $params The parameters to passe.
     * @return mixed          The message instance response.
     */
    public function __call($method, $params)
    {
        call_user_func_array([$this->_message, $method], $params);
        return $this;
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
        $startIndex = $this->_ordered ? MethodCalls::lastFindIndex() : 0;
        $report = MethodCalls::find($this->_actual, $this->_message, $startIndex, $this->_message->times());
        $this->_report = $report;
        $this->_buildDescription($startIndex);
        return $report['success'];
    }

    /**
     * Gets the message instance.
     *
     * @return object
     */
    public function message()
    {
        return $this->_message;
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
        $with = $this->_message->params();
        $times = $this->_message->times();

        $report = $this->_report;

        $expectedTimes = $times ? ' the expected times' : '';
        $expectedParameters = $with ? ' with expected parameters' : '';

        $this->_description['description'] = "receive the expected method{$expectedParameters}{$expectedTimes}.";

        $calledTimes = count($report['params']);

        if (!$calledTimes || ($calledTimes !== $times && $times)) {
            $logged = [];
            foreach(MethodCalls::logs($this->_actual, $startIndex) as $log) {
                $logged[] = $log['static'] ? '::' . $log['name'] : $log['name'];
            }

            $this->_description['params']['actual received calls'] = $logged;
        } elseif ($calledTimes) {
            $this->_description['params']['actual received'] = $this->_expected;
            $this->_description['params']['actual received times'] = $calledTimes;
            if ($with !== null) {
               $this->_description['params']['actual received parameters list'] = $report['params'];
            }
        }

        $this->_description['params']['expected to receive'] = $this->_expected;

        if ($with !== null) {
            $this->_description['params']['expected parameters'] = $with;
        }

        if ($times) {
            $this->_description['params']['expected received times'] = $times;
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
