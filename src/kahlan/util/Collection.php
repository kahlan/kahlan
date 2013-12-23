<?php
/**
 * Kahlan: PHP Testing Framework
 *
 * @copyright     Copyright 2013, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace kahlan\util;

/**
 * The parent class for all collection objects. Contains methods for collection iteration,
 * conversion, and filtering. Implements `ArrayAccess`, `Iterator`, and `Countable`.
 *
 * Collection objects can act very much like arrays. This is especially evident in creating new
 * objects, or by converting Collection into an actual array:
 *
 * {{{
 * $coll = new Collection();
 * $coll[] = 'foo';
 * // $coll[0] --> 'foo'
 *
 * $coll = new Collection(array('data' => array('foo')));
 * // $coll[0] --> 'foo'
 *
 * $array = $coll->to('array');
 * }}}
 *
 * Apart from array-like data access, Collections allow for filtering and iteration methods:
 *
 * {{{
 *
 * $coll = new Collection(array('data' => array(0, 1, 2, 3, 4)));
 *
 * $coll->first();   // 0
 * $coll->current(); // 0
 * $coll->next();    // 1
 * $coll->next();    // 2
 * $coll->next();    // 3
 * $coll->prev();    // 2
 * $coll->rewind();  // 0
 * }}}
 *
 * The primary purpose of the `Collection` class is to enable simple, efficient access to groups
 * of similar objects, and to perform operations against these objects using anonymous functions.
 *
 * The `map()` and `each()` methods allow you to perform operations against the entire set of values
 * in a `Collection`, while `find()` and `first()` allow you to search through values and pick out
 * one or more.
 *
 * The `Collection` class also supports dispatching methods against a set of objects, if the method
 * is supported by all objects. For example: {{{
 * class Task {
 * 	public function run($when) {
 * 		// Do some work
 * 	}
 * }
 *
 * $data = array(
 * 	new Task(array('task' => 'task 1')),
 * 	new Task(array('task' => 'task 2')),
 * 	new Task(array('task' => 'task 3'))
 * );
 * $tasks = new Collection(compact('data'));
 *
 * // $result will contain an array, and each element will be the return
 * // value of a run() method call:
 * $result = $tasks->invoke('run', array('now'));
 *
 * // Alternatively, the method can be called natively, with the same result:
 * $result = $tasks->run('now');
 * }}}
 *
 * @link http://us.php.net/manual/en/class.arrayaccess.php PHP Manual: ArrayAccess Interface
 * @link http://us.php.net/manual/en/class.iterator.php PHP Manual: Iterator Interface
 * @link http://us.php.net/manual/en/class.countable.php PHP Manual: Countable Interface
 */
class Collection implements \ArrayAccess, \Iterator, \Countable {

	/**
	 * The items contained in the collection.
	 *
	 * @var array
	 */
	protected $_data = [];

	/**
	 * Construct the collection object
	 */
	public function __construct($options = []) {
		$defaults = ['data' => []];
		$options += $defaults;
		$this->_data = $options['data'];
	}

	/**
	 * Handles dispatching of methods against all items in the collection.
	 *
	 * @param  string $method The name of the method to call on each instance in the collection.
	 * @param  array  $params The parameters to pass on each method call.
	 * @param  array  $options Specifies options for how to run the given method against the object
	 *                collection. The available options are:
	 *                - `'collect'`: If `true`, the results of this method call will be returned
	 *                wrapped in a new `Collection` object or subclass.
	 *                - `'merge'`: Used primarily if the method being invoked returns an array.  If
	 *                set to `true`, merges all results arrays into one.
	 * @return mixed  Returns either an array of the return values of the methods, or the return
	 *                values wrapped in a `Collection` instance.
	 */
	public function invoke($method, $params = [], $options = []) {
		$class = get_class($this);
		$defaults = ['merge' => false, 'collect' => false];
		$options += $defaults;
		$data = [];
		foreach ($this as $object) {
			$value = call_user_func_array([$object, $method], $params);
			$options['merge'] ? $data = array_merge($data, $value) : $data[$this->key()] = $value;
		}
		return $options['collect'] ? new $class(compact('data')) : $data;
	}

	/**
	 * Hook to handle dispatching of methods against all items in the collection.
	 *
	 * @param string $method
	 * @param array $parameters
	 * @return mixed
	 */
	public function __call($method, $parameters = []) {
		return $this->invoke($method, $parameters);
	}

	/**
	 * Filters a copy of the items in the collection.
	 *
	 * @param  callback $filter Callback to use for filtering.
	 * @param  array    $options The available options are:
	 *                 - `'collect'`: If `true`, the results will be returned wrapped in a new
	 *                 `Collection` object or subclass.
	 * @return mixed    The filtered items. Will be an array unless `'collect'` is defined in the
	 *                 `$options` argument, then an instance of this class will be returned.
	 */
	public function find($filter, $options = []) {
		$defaults = ['collect' => true];
		$options += $defaults;
		$data = array_filter($this->_data, $filter);

		if ($options['collect']) {
			$class = get_class($this);
			$data = new $class(compact('data'));
		}
		return $data;
	}

	/**
	 * Returns the first non-empty value in the collection after a filter is applied, or rewinds the
	 * collection and returns the first value.
	 *
	 * @param  callback $filter A closure through which collection values will be passed.
	 *                  If the return value of this function is non-empty, it will be returned as
	 *                  the result of the method call. If `null`, the collection is rewound
	 *                  (see `rewind()`) and the first item is returned.
	 * @return mixed    Returns the first non-empty collection value returned from `$filter`.
	 */
	public function first($filter = null) {
		if (!$filter) {
			return $this->rewind();
		}

		foreach ($this as $item) {
			if ($filter($item)) {
				return $item;
			}
		}
	}

	/**
	 * Applies a callback to all items in the collection.
	 *
	 * @param  callback $filter The filter to apply.
	 * @return object   This collection instance.
	 */
	public function each($filter) {
		$this->_data = array_map($filter, $this->_data);
		return $this;
	}

	/**
	 * Applies a callback to a copy of all data in the collection
	 * and returns the result.
	 *
	 * @param  callback $filter The filter to apply.
	 * @param  array    $options The available options are:
	 *                  - `'collect'`: If `true`, the results will be returned wrapped
	 *                  in a new `Collection` object or subclass.
	 * @return mixed    The filtered items. Will be an array unless `'collect'` is defined in the
	 *                  `$options` argument, then an instance of this class will be returned.
	 */
	public function map($filter, $options = []) {
		$defaults = ['collect' => true];
		$options += $defaults;
		$data = array_map($filter, $this->_data);

		if ($options['collect']) {
			$class = get_class($this);
			return new $class(compact('data'));
		}
		return $data;
	}

	/**
	 * Reduce, or fold, a collection down to a single value
	 *
	 * @param  callback $filter The filter to apply.
	 * @param  mixed    $initial Initial value
	 * @return mixed    A single reduced value
	 */
	public function reduce($filter, $initial = false) {
		return array_reduce($this->_data, $filter, $initial);
	}

	/**
	 * Sorts the objects in the collection.
	 *
	 * @param  callable $sorter The sorter for the data, can either be a sort function like
	 *                  natsort or a compare function like strcmp.
	 * @param  array    $options The available options are:
	 *                  - No options yet implemented
	 * @return $this, useful for chaining this with other methods.
	 */
	public function sort($sorter = 'sort', $options = []) {
		if (is_string($sorter) && strpos($sorter, 'sort') !== false && is_callable($sorter)) {
			call_user_func_array($sorter, [&$this->_data]);
		} elseif (is_callable($sorter)) {
			usort($this->_data, $sorter);
		}
		return $this;
	}

	/**
	 * Checks whether or not an offset exists.
	 *
	 * @param  string  $offset An offset to check for.
	 * @return boolean `true`  if offset exists, `false` otherwise.
	 */
	public function offsetExists($offset) {
		return array_key_exists($offset, $this->_data);
	}

	/**
	 * Returns the value at specified offset.
	 *
	 * @param  string $offset The offset to retrieve.
	 * @return mixed  Value at offset.
	 */
	public function offsetGet($offset) {
		return $this->_data[$offset];
	}

	/**
	 * Assigns a value to the specified offset.
	 *
	 * @param  string $offset The offset to assign the value to.
	 * @param  mixed  $value The value to set.
	 * @return mixed  The value which was set.
	 */
	public function offsetSet($offset, $value) {
		if (is_null($offset)) {
			return $this->_data[] = $value;
		}
		return $this->_data[$offset] = $value;
	}

	/**
	 * Unsets an offset.
	 *
	 * @param string $offset The offset to unset.
	 */
	public function offsetUnset($offset) {
		prev($this->_data);
		if (key($this->_data) === null) {
			$this->rewind();
		}
		unset($this->_data[$offset]);
	}

	/**
	 * Rewinds to the first item.
	 *
	 * @return mixed The current item after rewinding.
	 */
	public function rewind() {
		reset($this->_data);
		return current($this->_data);
	}

	/**
	 * Moves forward to the last item.
	 *
	 * @return mixed The current item after moving.
	 */
	public function end() {
		end($this->_data);
		return current($this->_data);
	}

	/**
	 * Checks if current position is valid.
	 *
	 * @return boolean `true` if valid, `false` otherwise.
	 */
	public function valid() {
		return key($this->_data) !== null;
	}

	/**
	 * Returns the current item.
	 *
	 * @return mixed The current item or `false` on failure.
	 */
	public function current() {
		return current($this->_data);
	}

	/**
	 * Returns the key of the current item.
	 *
	 * @return scalar Scalar on success or `null` on failure.
	 */
	public function key() {
		return key($this->_data);
	}

	/**
	 * Moves backward to the previous item.  If already at the first item,
	 * moves to the last one.
	 *
	 * @return mixed The current item after moving or the last item on failure.
	 */
	public function prev() {
		if (!prev($this->_data)) {
			end($this->_data);
		}
		return current($this->_data);
	}

	/**
	 * Move forwards to the next item.
	 *
	 * @return The current item after moving or `false` on failure.
	 */
	public function next() {
		next($this->_data);
		return current($this->_data);
	}

	/**
	 * Appends an item.
	 *
	 * @param mixed $value The item to append.
	 */
	public function append($value) {
		$this->_data[] = $value;
	}

	/**
	 * Counts the items of the object.
	 *
	 * @return integer Returns the number of items in the collection.
	 */
	public function count() {
		$count = iterator_count($this);
		$this->rewind();
		return $count;
	}

	/**
	 * Returns the item keys.
	 *
	 * @return array The keys of the items.
	 */
	public function keys() {
		return array_keys($this->_data);
	}

}

?>