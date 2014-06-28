<?php
/**
 * Kahlan: PHP Testing Framework
 *
 * @copyright     Copyright 2013, CrysaLEAD
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace kahlan\analysis\code;

class NamespaceDef extends NodeDef
{
    public $type = 'namespace';

    public $uses = [];

    public $name = '';
}
