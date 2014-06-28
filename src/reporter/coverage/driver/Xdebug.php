<?php
/**
 * Kahlan: PHP Testing Framework
 *
 * @copyright     Copyright 2013, CrysaLEAD
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace kahlan\reporter\coverage\driver;

use RuntimeException;

class Xdebug
{
    /**
     * Construct.
     */
    public function __construct()
    {
        if (!extension_loaded('xdebug')) {
            throw new RuntimeException('Xdebug is not loaded.');
        }

        if (!ini_get('xdebug.coverage_enable')) {
            throw new RuntimeException('You need to set `xdebug.coverage_enable = On` in your php.ini.');
        }
    }

    /**
     * Start code coverage.
     */
    public function start()
    {
        xdebug_start_code_coverage(XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE);
    }

    /**
     * Stop code coverage.
     *
     * @return array The collected coverage
     */
    public function stop()
    {
        $data = xdebug_get_code_coverage();
        xdebug_stop_code_coverage();

        $result = [];
        foreach ($data as $file => $coverage) {
            foreach ($coverage as $line => $value) {
                if ($line && $value !== -2) {
                    $result[$file][$line - 1] = $value === -1 ? 0 : $value;
                }
            }
        }
        return $result;
    }

}
