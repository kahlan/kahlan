<?php
/**
 * Kahlan: PHP Testing Framework
 *
 * @copyright     Copyright 2013, CrysaLEAD
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

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

    protected $_actual = null;

    protected $_expected = null;

    protected $_backtrace = null;

    protected $_call = null;

    protected $_message = null;

    protected $_report = null;

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
        $this->_backtrace = Debugger::backtrace(['start' => 4]);
    }

    public function __call($method, $params)
    {
        return call_user_func_array([$this->_message, $method], $params);
    }

    public function resolve($report)
    {
        $call = $this->_classes['call'];
        $success = !!$call::find($this->_actual, $this->_message);
        if (!$success) {
            $this->report($report);
        }
        return $success;
    }

    public function message()
    {
        return $this->_message;
    }

    public function backtrace()
    {
        return $this->_backtrace;
    }

    /**
     * Expect that `$actual` receive the `$expected` message.
     *
     * @param  mixed   $actual The actual value.
     * @param  mixed   $expected The expected message.
     * @return boolean
     */
    public static function match($actual, $expected)
    {
        $class = get_called_class();
        return new static($actual, $expected);
    }

    public static function description($report)
    {
        return $report['instance']->report();
    }

    public function report($report = null)
    {
        if ($report === null) {
            return $this->_report;
        }
        $call = $this->_classes['call'];

        $with = $this->_message->getWith();
        $this->_message->with();
        if ($log = $call::find($this->_actual, $this->_message)) {
            $this->_report['description'] = 'receive correct parameters.';
            $this->_report['params']['actual with'] = $with;
            $this->_report['params']['expected with'] = $log['params'];
            return;
        }

        $this->_report['description'] = 'receive the correct message.';
        $this->_report['params']['actual received'] = [];
        foreach($call::find($this->_actual) as $log) {
            $this->_report['params']['actual received'][] = $log['name'];
        }
        $this->_report['params']['expected'] = $report['params']['expected'];
    }
}
