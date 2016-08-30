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
        return call_user_func_array([$this->_message, $method], $params);
    }

    /**
     * Resolves the matching.
     *
     * @return boolean Returns `true` if successfully resolved, `false` otherwise.
     */
    public function resolve()
    {
        $report = FunctionCalls::find($this->_message, 0, $this->_message->times());
        $this->_report = $report;
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
     * Returns the description report.
     */
    public function description($not)
    {
        $report = $this->_report;
        $times = $this->_message->times();
        $with = $this->_message->params();

        $this->_description['description'] = 'called with correct params.';

        $this->_description['params']["actual number of times that `{$this->_actual}()` has been called with correct params"] = $report['matches'];

        if ($with && count($report['params'])) {
            $this->_description['params']['received params array'] = $report['params'];
        }

        if ($times) {
            $this->_description['params']['expected called times'] = $times;
        } elseif ($not) {
            $this->_description['params']['expected called times'] = $report['matches'];
        }

        if ($with) {
            $this->_description['params']['expected with'] = $with;
        }

        return $this->_description;
    }

}
