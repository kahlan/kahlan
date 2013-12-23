<?php
/**
 * Kahlan: PHP Testing Framework
 *
 * @copyright     Copyright 2013, CrysaLEAD
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace kahlan\plugin\stub;

class Method extends \kahlan\plugin\call\Message {

	/**
	 * Current `Method::$_returns` index value to use.
	 *
	 * @var array
	 */
	protected $_index = 0;

	/**
	 * Implementation
	 *
	 * @var mixed
	 */
	protected $_closure = null;

	/**
	 * Return values
	 *
	 * @var mixed
	 */
	protected $_returns = [];

	public function __construct($options = []) {
		$defaults = ['closure' => null, 'params' => [], 'returns' => [], 'static' => false];
		$options += $defaults;
		parent::__construct($options);
		$this->_closure = $options['closure'];
		$this->_returns = $options['returns'];
	}

	/**
	 * Stub class method.
	 *
	 * @param string $name method name.
	 */
	public function __invoke($params) {
		if ($this->_closure) {
			$closure = $this->_closure;
			return $closure($params);
		}
		if (isset($this->_returns[$this->_index])) {
			return $this->_returns[$this->_index++];
		}
		return $this->_returns ? end($this->_returns) : null;
	}

	/**
	 * Set the method logic
	 *
	 * @param Closure $closure The logic.
	 */
	public function run($closure) {
		if ($this->_returns) {
			throw new Exception("Some return values are already set.");
		}
		if (!is_callable($closure)) {
			throw new Exception("The passed parameter is not callable.");
		}
		$this->_closure = $closure;
	}

	/**
	 * Set return values.
	 *
	 * @param mixed <0,n> Return value(s).
	 */
	public function andReturn() {
		if ($this->_closure) {
			throw new Exception("Closure already set.");
		}
		if (func_num_args()) {
			$this->_returns = func_get_args();
		}
	}

}

?>