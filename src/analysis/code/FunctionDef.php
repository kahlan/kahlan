<?php
/**
 * Kahlan: PHP Testing Framework
 *
 * @copyright     Copyright 2013, CrysaLEAD
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace kahlan\analysis\code;

class FunctionDef extends NodeDef
{
    public $type = 'function';

    public $isClosure = false;

    public $isMethod = false;

    public $visibility = [];

    public $name = '';

    public $args = [];

    public function argsToParams($reference = false)
    {
        $args = [];
        foreach ($this->args as $key => $value) {
            $value = is_int($key) ? $value : $key;
            $ref = $reference ? '\&?' : '';
            preg_match("/({$ref}\\\$[\\\a-z_\\x7f-\\xff][a-z0-9_\\x7f-\\xff]*)/i", $value, $match);
            $args[] = $match[1];
        }
        return join(', ', $args);
    }

}
