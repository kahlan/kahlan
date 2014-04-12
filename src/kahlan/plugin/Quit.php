<?php
/**
 * Kahlan: PHP Testing Framework
 *
 * @copyright     Copyright 2013, CrysaLEAD
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace kahlan\plugin;

use kahlan\QuitException;

class Quit {

	/**
	 * Indicates if the `exit` or `die` statements are disabled or not.
	 */
	protected static $_disabled = false;

	/**
	 * Return the status of the quit statements.
	 *
	 * @return boolean $active
	 */
	public static function disabled() {
		return static::$_enabled;
	}

	/**
	 * Enabled/Disable the `exit`, `die` statements.
	 *
	 * @param boolean $active
	 */
	public static function disable($disable = true) {
		static::$_disabled = $disable;
	}

	/**
	 * Run a controlled quit statement.
	 *
	 * @param  integer              $status Use 0 for a successful exit.
	 * @throws kahlan\QuitException         Only if disableed is `true`.
	 */
	public static function quit($status = 0) {
		if (!static::$_disabled) {
			exit($status);
		}
		throw new QuitException('Exit statement occured', $status);
	}

	/**
	 * Clear class to default values.
	 */
	public static function clear() {
		static::$_disabled = false;
	}
}

?>