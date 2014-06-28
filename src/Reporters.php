<?php
/**
 * Kahlan: PHP Testing Framework
 *
 * @copyright     Copyright 2013, CrysaLEAD
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace kahlan;

/**
 * Reporter manager
 */
class Reporters {

    /**
     * The registered reporters
     */
    protected $_reporters = [];

    /**
     * Adds a reporter
     *
     * @param  string         $name     The reporter name.
     * @param  object         $reporter A reporter.
     * @return object|boolean The added reporter instance or `false` on failure.
     */
    public function add($name, $reporter) {
        if (!is_object($reporter)) {
            return false;
        }
        $this->_reporters[$name] = $reporter;
    }

    /**
     * Gets a reporter
     *
     * @param  string $name The reporter name.
     * @return mixed  The reporter or `null` if not found.
     */
    public function get($name) {
        if (isset($this->_reporters[$name])) {
            return $this->_reporters[$name];
        }
    }

    /**
     * Checks if a reporter exist.
     *
     * @param  string $name The reporter name.
     * @return boolean
     */
    public function exists($name) {
        return isset($this->_reporters[$name]);
    }

    /**
     * Removes a reporter.
     *
     * @param string $name The reporter name.
     */
    public function remove($name) {
        unset($this->_reporters[$name]);
    }

    /**
     * Removes all reporters.
     *
     * @param string $name The reporter name.
     */
    public function clear() {
        $this->_reporters = [];
    }

    /**
     * Send a report
     *
     * @param string $type The name of the report.
     * @param array  $data The data to report.
     */
    public function process($type, $data = null) {
        foreach ($this->_reporters as $reporter) {
            $reporter->$type($data);
        }
    }
}

?>