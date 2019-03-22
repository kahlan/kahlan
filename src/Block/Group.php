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
     * Indicates if the group has been loaded or not.
     *
     * @var boolean
     */
    protected $_loaded = false;

    /**
     * The children array.
     *
     * @var Group[]|Specification[]
     */
    protected $_children = [];

    /**
     * Group statistics.
     *
     * @var array
     */
    protected $_stats = null;

    /**
     * Group state.
     *
     * @var array
     */
    protected $_enabled = true;

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

    /**
     * Builds the group stats.
     *
     * @return array The group stats.
     */
    public function stats()
    {
        if ($this->_stats !== null) {
            return $this->_stats;
        }

        Suite::push($this);

        $builder = function ($block) {
            $block->load();
            $normal = 0;
            $inactive = 0;
            $focused = 0;
            $excluded = 0;

            foreach ($block->children() as $child) {
                if ($block->excluded()) {
                    $child->type('exclude');
                }
                if ($child instanceof Group) {
                    $result = $child->stats();
                    if ($child->focused() && !$result['focused']) {
                        $focused += $result['normal'];
                        $excluded += $result['excluded'];
                        $child->broadcastFocus();
                    } elseif (!$child->enabled()) {
                        $inactive += $result['normal'];
                        $focused += $result['focused'];
                        $excluded += $result['excluded'];
                    } else {
                        $normal += $result['normal'];
                        $focused += $result['focused'];
                        $excluded += $result['excluded'];
                    }
                } else {
                    switch ($child->type()) {
                        case 'exclude':
                            $excluded++;
                            break;
                        case 'focus':
                            $focused++;
                            break;
                        default:
                            $normal++;
                            break;
                    }
                }
            }
            return compact('normal', 'inactive', 'focused', 'excluded');
        };

        if (Suite::$PHP >= 7 && !defined('HHVM_VERSION')) {
            try {
                $stats = $builder($this);
            } catch (Throwable $exception) {
                $this->log()->type('errored');
                $this->log()->exception($exception);

                $stats = [
                    'normal' => 0,
                    'focused' => 0,
                    'excluded' => 0
                ];
            }
        } else {
            $stats = $builder($this);
        }

        Suite::pop();
        return $stats;
    }

    /**
     * Splits the specs in different partitions and only enable one.
     *
     * @param integer $index The partition index to enable.
     * @param integer $total The total of partitions.
     */
    public function partition($index, $total)
    {
        $index = (integer) $index;
        $total = (integer) $total;
        if (!$index || !$total || $index > $total) {
            throw new Exception("Invalid partition parameters: {$index}/{$total}");
        }

        $groups = [];
        $partitions = [];
        $partitionsTotal = [];

        for ($i = 0; $i < $total; $i++) {
            $partitions[$i] = [];
            $partitionsTotal[$i] = 0;
        }

        $children = $this->children();

        foreach ($children as $key => $child) {
            $groups[$key] = $child->stats()['normal'];
            $child->enabled(false);
        }
        asort($groups);

        foreach ($groups as $key => $value) {
            $i = array_search(min($partitionsTotal), $partitionsTotal);
            $partitions[$i][] = $key;
            $partitionsTotal[$i] += $groups[$key];
        }

        foreach ($partitions[$index - 1] as $key) {
            $children[$key]->enabled(true);
        }
    }

    /**
     * Set/get the enable value.
     *
     * @param  string $enable The enable value.
     * @return mixed
     */
    public function enabled($enable = null)
    {
        if (!func_num_args()) {
            return $this->_enabled;
        }
        $this->_enabled = $enable;
        return $this;
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
        array_unshift($this->_callbacks['afterAll'], $this->_bindScope($closure));
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
        array_unshift($this->_callbacks['afterEach'], $this->_bindScope($closure));
        return $this;
    }

    /**
     * Load the group.
     */
    public function load()
    {
        if ($this->_loaded) {
            return;
        }
        $this->_loaded = true;
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
        if (!$this->enabled() && !$this->focused()) {
            return;
        }
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
        $this->report('suiteStart', $this);
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

        $this->report('suiteEnd', $this);
    }

    /**
     * Runs a callback.
     *
     * @param string $name The name of the callback (i.e `'beforeEach'` or `'afterEach'`).
     */
    public function runCallbacks($name, $recursive = true)
    {
        $instances = $recursive ? $this->parents(true) : [$this];
        if (strncmp($name, 'after', 5) === 0) {
            $instances = array_reverse($instances);
        }
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
            if ($child->type() !== 'normal') {
                continue;
            }
            $child->type('focus');
            if ($child instanceof Group) {
                $child->broadcastFocus();
            }
        }
    }

}
