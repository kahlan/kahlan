<?php
namespace kahlan\matcher;

use kahlan\analysis\Debugger;

class ToReceive
{
    /**
     * Class dependencies.
     *
     * @var array
     */
    protected $_classes = [
        'call' => 'kahlan\plugin\Call'
    ];

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
    protected $_call = null;

    /**
     * The message instance.
     *
     * @var object
     */
    protected $_message = null;

    /**
     * The description report.
     *
     * @var array
     */
    protected $_report = null;

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
     * Returns the description report.
     *
     * @return array The description report.
     */
    public static function description($report)
    {
        return $report['instance']->report();
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
        $this->_actual = $actual;
        $this->_expected = $expected;
        $call = $this->_classes['call'];
        $this->_call = new $call($actual);
        $this->_message = $this->_call->method($expected);
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
        return call_user_func_array([$this->_message, $method], $params);
    }

    /**
     * Resolves the matching.
     *
     * @param  string  $report The description report.
     * @return boolean         Returns `true` if successfully resolved, `false` otherwise.
     */
    public function resolve($report)
    {
        $call = $this->_classes['call'];
        $success = !!$call::find($this->_actual, $this->_message);
        if (!$success) {
            $this->report($report);
        }
        return $success;
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
     * Returns the description report.
     *
     * @return array The description report.
     */
    public function report($report = null, $startIndex = 0)
    {
        if ($report === null) {
            return $this->_report;
        }
        $call = $this->_classes['call'];

        $with = $this->_message->params();
        $this->_message->with();

        if ($log = $call::find($this->_actual, $this->_message, $startIndex)) {
            $this->_report['description'] = 'receive correct parameters.';
            $this->_report['params']['actual with'] = $log['params'];
            $this->_report['params']['expected with'] = $with;
            return;
        }

        $this->_report['description'] = 'receive the correct message.';
        $called = [];
        foreach($call::find($this->_actual, null, $startIndex) as $log) {
            $called[] = $log['static'] ? '::' . $log['name'] : $log['name'];
        }
        $this->_report['params']['actual received'] = $called;
        $this->_report['params']['expected'] = $report['params']['expected'];
    }

}
