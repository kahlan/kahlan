<?php
namespace Kahlan\Block;

use Closure;
use Exception;
use Throwable;
use Kahlan\Suite;
use Kahlan\Scope\Group as Scope;

class Group extends \Kahlan\Block
{
    /**
     * The each callbacks.
     *
     * @var array
     */
    protected $_callbacks = [
        'beforeAll'  => [],
        'afterAll'   => [],
        'beforeEach' => [],
        'afterEach'  => [],
    ];

    /**
     * The children array.
     *
     * @var Group[]|Specification[]
     */
    protected $_children = [];

    /**
     * The Constructor.
     *
     * @param array $config The Group config array. Options are:
     *                      -`'name'`    _string_ : the type of the suite.
     */
    public function __construct($config = [])
    {
        parent::__construct($config);

        $this->_scope = new Scope(['block' => $this]);
        $this->_closure = $this->_bindScope($this->_closure);
    }

    /**
     * Gets children.
     *
     * @return array The array of children instances.
     */
    public function children()
    {
        return $this->_children;
    }

    /* Adds a group/class related spec.
     *
     * @param  string  $message Description message.
     * @param  Closure $closure A test case closure.
     *
     * @return Group
     */
    public function describe($message, $closure, $timeout = null, $type = 'normal')
    {
        $suite = $this->suite();
        $parent = $this;
        $timeout = $timeout !== null ? $timeout : $this->timeout();
        $group = new Group(compact('message', 'closure', 'suite', 'parent', 'timeout', 'type'));

        return $this->_children[] = $group;
    }

    /**
     * Adds a context related spec.
     *
     * @param  string  $message Description message.
     * @param  Closure $closure A test case closure.
     * @param  null    $timeout
     * @param  string  $type
     *
     * @return Group
     */
    public function context($message, $closure, $timeout = null, $type = 'normal')
    {
        return $this->describe($message, $closure, $timeout, $type);
    }

    /**
     * Adds a spec.
     *
     * @param  string|Closure $message Description message or a test closure.
     * @param  Closure        $closure A test case closure.
     * @param  string         $type    The type.
     *
     * @return Specification
     */
    public function it($message, $closure = null, $timeout = null, $type = 'normal')
    {
        $suite = $this->suite();
        $parent = $this;
        $timeout = $timeout !== null ? $timeout : $this->timeout();
        $spec = new Specification(compact('message', 'closure', 'suite', 'parent', 'timeout', 'type'));
        $this->_children[] = $spec;

        return $this;
    }

    /**
     * Executed before tests.
     *
     * @param  Closure $closure A closure
     *
     * @return self
     */
    public function beforeAll($closure)
    {
        $this->_callbacks['beforeAll'][] = $this->_bindScope($closure);
        return $this;
    }

    /**
     * Executed after tests.
     *
     * @param  Closure $closure A closure
     *
     * @return self
     */
    public function afterAll($closure)
    {
        $this->_callbacks['afterAll'][] = $this->_bindScope($closure);
        return $this;
    }

    /**
     * Executed before each tests.
     *
     * @param  Closure $closure A closure
     *
     * @return self
     */
    public function beforeEach($closure)
    {
        $this->_callbacks['beforeEach'][] = $this->_bindScope($closure);
        return $this;
    }

    /**
     * Executed after each tests.
     *
     * @param  Closure $closure A closure
     *
     * @return self
     */
    public function afterEach($closure)
    {
        $this->_callbacks['afterEach'][] = $this->_bindScope($closure);
        return $this;
    }

    /**
     * Load the group.
     */
    public function load()
    {
        if (!$closure = $this->closure()) {
            return;
        }
        return $this->_suite->runBlock($this, $closure, 'group');
    }

    /**
     * Group execution helper.
     */
    protected function _execute()
    {
        foreach ($this->_children as $child) {
            if ($this->suite()->failfast()) {
                break;
            }
            $this->_passed = $child->process() && $this->_passed;
        }
    }

    /**
     * Start group execution helper.
     */
    protected function _blockStart()
    {
        if ($this->message()) {
            $this->report('suiteStart', $this);
        }
        $this->runCallbacks('beforeAll', false);
    }

    /**
     * End group block execution helper.
     */
    protected function _blockEnd($runAfterAll = true)
    {
        if ($runAfterAll) {
            if (Suite::$PHP >= 7 && !defined('HHVM_VERSION')) {
                try {
                    $this->runCallbacks('afterAll', false);
                } catch (Throwable $exception) {
                    $this->_exception($exception);
                }
            } else {
                try {
                    $this->runCallbacks('afterAll', false);
                } catch (Exception $exception) {
                    $this->_exception($exception);
                }
            }
        }

        $this->suite()->autoclear();

        $type = $this->log()->type();
        if ($type === 'failed' || $type === 'errored') {
            $this->_passed = false;
            $this->suite()->failure();
            $this->summary()->log($this->log());
        }

        if ($this->message()) {
            $this->report('suiteEnd', $this);
        }
    }

    /**
     * Runs a callback.
     *
     * @param string $name The name of the callback (i.e `'beforeEach'` or `'afterEach'`).
     */
    public function runCallbacks($name, $recursive = true)
    {
        $instances = $recursive ? $this->parents(true) : [$this];
        foreach ($instances as $instance) {
            foreach ($instance->_callbacks[$name] as $closure) {
                $this->_suite->runBlock($this, $closure, $name);
            }
        }
    }

    /**
     * Gets callbacks.
     *
     * @param  string $type The type of callbacks to get.
     *
     * @return array        The array callbacks instances.
     */
    public function callbacks($type)
    {
        return isset($this->_callbacks[$type]) ? $this->_callbacks[$type] : [];
    }

    /**
     * Apply focus downward to the leaf.
     */
    public function broadcastFocus()
    {
        foreach ($this->_children as $child) {
            $child->type('focus');
            if ($child instanceof Group) {
                $child->broadcastFocus();
            }
        }
    }

}
