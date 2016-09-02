<?php
namespace Kahlan\Matcher;

use Kahlan\Analysis\Debugger;
use Kahlan\Plugin\Monkey;
use Kahlan\Plugin\Call\FunctionCalls;

class ToBeCalled
{
    /**
     * A fully-namespaced function name.
     *
     * @var string|object
     */
    protected $_actual = null;

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
     * Message params.
     *
     * @var array
     */
    protected $_params = [];

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
     * Checks that `$actual` will be called.
     *
     * @param  mixed   $actual   The actual value.
     * @param  mixed   $expected Unused.
     * @return boolean
     */
    public static function match($actual)
    {
        return new static($actual);
    }

    /**
     * Constructor
     *
     * @param string|object $actual   A fully-namespaced class name or an object instance.
     * @param string        $expected The expected method method name to be called.
     */
    public function __construct($actual)
    {
        $this->_actual = $actual;

        $this->_calls = new FunctionCalls($actual);
        $this->_message = $this->_calls->message();
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
     * Sets the stub logic.
     *
     * @param Closure $closure The logic.
     */
    public function andRun($closure)
    {
        Monkey::patch($this->_actual, $closure);
    }

    /**
     * Set. return values.
     *
     * @param mixed ... <0,n> Return value(s).
     */
    public function andReturn()
    {
        $args = func_get_args();
        Monkey::patch($this->_actual, function() use ($args) {
            static $index = 0;
            if (isset($args[$index])) {
                return $args[$index++];
            }
            return $args ? end($args) : null;
        });
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
        $startIndex = $this->_ordered ? FunctionCalls::lastFindIndex() : 0;
        $report = FunctionCalls::find($this->_message, $startIndex, $this->_message->times());
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

        $this->_description['description'] = "be called{$expectedParameters}{$expectedTimes}.";

        $calledTimes = count($report['params']);

        $this->_description['params']['actual'] = $this->_actual . '()';
        $this->_description['params']['actual called times'] = $calledTimes;

        if ($calledTimes && $with !== null) {
            $this->_description['params']['actual called parameters list'] = $report['params'];
        }

        $this->_description['params']['expected to be called'] = $this->_actual . '()';

        if ($with !== null) {
            $this->_description['params']['expected parameters'] = $with;
        }

        if ($times) {
            $this->_description['params']['expected called times'] = $times;
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
