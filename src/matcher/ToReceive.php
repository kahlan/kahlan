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
    protected $_description = [];

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
        $call = $this->_classes['call'];

        $this->_actual    = $actual;
        $this->_expected  = $expected;
        $this->_call      = new $call($actual);
        $this->_message   = $this->_call->method($expected);
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
        $call = $this->_classes['call'];
        $success = !!$call::find($this->_actual, $this->_message);
        $this->_buildDescription();
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
     * Build the description of the runned `::match()` call.
     *
     * @param mixed $startIndex The startIndex in calls log.
     */
    public function _buildDescription($startIndex = 0)
    {
        $call = $this->_classes['call'];

        $with = $this->_message->params();
        $this->_message->with();

        if ($log = $call::find($this->_actual, $this->_message, $startIndex)) {
            $this->_description['description'] = 'receive correct parameters.';
            $this->_description['params']['actual with'] = $log['params'];
            $this->_description['params']['expected with'] = $with;
            return;
        }

        $this->_description['description'] = 'receive the correct message.';
        $called = [];
        foreach($call::find($this->_actual, null, $startIndex) as $log) {
            $called[] = $log['static'] ? '::' . $log['name'] : $log['name'];
        }
        $this->_description['params']['actual received'] = $called;
        $this->_description['params']['expected'] = $this->_expected;
    }

    /**
     * Returns the description report.
     */
    public function description()
    {
        return $this->_description;
    }

}
