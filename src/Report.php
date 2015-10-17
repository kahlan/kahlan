<?php
namespace kahlan;

use kahlan\analysis\Debugger;

class Report
{
    /**
     * The scope context instance.
     *
     * @var object
     */
    protected $_scope = null;

    /**
     * The type of the report.
     *
     * @var object
     */
    protected $_type = null;

    /**
     * The file path related to the report.
     *
     * @var string
     */
    protected $_file = null;

    /**
     * The line related to the report.
     *
     * @var string
     */
    protected $_line = null;

    /**
     * If it's an inverted expectation.
     *
     * @var boolean
     */
    protected $_not = false;

    /**
     * The matcher description result.
     *
     * @var string
     */
    protected $_description = null;

    /**
     * The matcher class name from which this report is related.
     *
     * @var string
     */
    protected $_matcher = null;

    /**
     * The matcher name from which this report is related.
     *
     * @var string
     */
    protected $_matcherName = null;

    /**
     * The matcher params.
     *
     * @var array
     */
    protected $_params = [];

    /**
     * The related exception.
     *
     * @var string
     */
    protected $_exception = null;

    /**
     * The reports of executed expectations.
     *
     * @var array
     */
    protected $_childs = [];

    /**
     * The Constructor.
     *
     * @param array $config The Suite config array. Options are:
     *                      -`'scope'` _object_: the scope context instance.
     */
    public function __construct($config = [])
    {
        $defaults = [
            'scope'       => null,
            'type'        => 'pass',
            'not'         => false,
            'description' => null,
            'matcher'     => null,
            'matcherName' => null,
            'params'      => [],
            'backtrace'   => [],
            'exception'   => null
        ];
        $config += $defaults;

        $this->_scope       = $config['scope'];
        $this->_type        = $config['type'];
        $this->_not         = $config['not'];
        $this->_description = $config['description'];
        $this->_matcher     = $config['matcher'];
        $this->_matcherName = $config['matcherName'];
        $this->_params      = $config['params'];
        $this->_backtrace   = $config['backtrace'];
        $this->_exception   = $config['exception'];

        if ($this->_backtrace) {
            $trace = reset($this->_backtrace);
            $this->_file = preg_replace('~' . preg_quote(getcwd(), '~') . '~', '', $trace['file']);
            $this->_line = $trace['line'];
        }
    }

    /**
     * Gets the scope context of the report.
     *
     * @return object
     */
    public function scope()
    {
        return $this->_scope;
    }

    /**
     * Gets the type of the report.
     *
     * @return string
     */
    public function type()
    {
        return $this->_type;
    }

    /**
     * Gets the not boolean.
     *
     * @return string
     */
    public function not()
    {
        return $this->_not;
    }

    /**
     * Gets the matcher description result.
     *
     * @return string
     */
    public function description()
    {
        return $this->_description;
    }

    /**
     * Gets the matcher class name related to the report.
     *
     * @return string
     */
    public function matcher()
    {
        return $this->_matcher;
    }

    /**
     * Gets the matcher name related to the report.
     *
     * @return string
     */
    public function matcherName()
    {
        return $this->_matcherName;
    }

    /**
     * Gets the matcher params.
     *
     * @return array
     */
    public function params()
    {
        return $this->_params;
    }

    /**
     * Gets the backtrace related to the report.
     *
     * @return array
     */
    public function backtrace()
    {
        return $this->_backtrace;
    }

    /**
     * Gets the exception related to the report.
     *
     * @return object
     */
    public function exception()
    {
        return $this->_exception;
    }

    /**
     * Gets file path related to the report.
     *
     * @return array
     */
    public function file()
    {
        return $this->_file;
    }

    /**
     * Gets line related to the report.
     *
     * @return array
     */
    public function line()
    {
        return $this->_line;
    }

    /**
     * Gets the scope related messages.
     *
     * @return array
     */
    public function messages()
    {
        return $this->scope()->messages();
    }

    /**
     * Gets all executed expectations reports.
     *
     * @return array The executed expectations reports.
     */
    public function childs()
    {
        return $this->_childs;
    }

    /**
     * Adds an expectation report and emits a report event.
     *
     * @param array $data The report data.
     */
    public function add($type, $data = [])
    {
        $data['type'] = $type;
        if ($type !== 'pass' && $type !== 'skip') {
            $this->scope()->failure();
        }

        $data['backtrace'] = $this->_backtrace($data);
        $this->_type = ($data['type'] !== 'pass') ? $data['type'] : 'pass';
        $child = new static($data + ['scope' => $this->_scope]);
        $this->_childs[] = $child;
        $this->scope()->dispatch($child);
    }

    /**
     * Helper which extracts the backtrace of a report.
     *
     * @param array $data The report data.
     */
    public function _backtrace($data)
    {
        if (isset($data['exception'])) {
            return Debugger::backtrace(['trace' => $data['exception']]);
        }
        $type = $data['type'];
        $depth = ($type === 'pass' || $type === 'fail' | $type === 'skip') ? 1 : null;
        if (!isset($data['backtrace'])) {
            $data['backtrace'] = [];
        }
        return Debugger::focus($this->scope()->backtraceFocus(), $data['backtrace'], $depth);
    }
}
