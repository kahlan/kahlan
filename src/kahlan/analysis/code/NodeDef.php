<?php
/**
 * Kahlan: PHP Testing Framework
 *
 * @copyright     Copyright 2013, CrysaLEAD & Craftsmen
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace kahlan\analysis\code;

class NodeDef {

	public $type = 'none';

	public $namespace = null;

	public $parent = null;

	public $inPhp = false;

	public $body = '';

	public $close = '';

	public $tree = [];

	public $lines = [
		'content' => [],
		'start' => null,
		'stop'  => 0
	];

	public $processable = true;

	public function __construct($body = '', $type = null) {
		if ($type) {
			$this->type = $type;
		}
		$this->body = $body;
	}

	public function __toString() {
		$childs = '';
		foreach ($this->tree as $node) {
			$childs .= (string) $node;
		}
		return $this->body . $childs . $this->close;
	}
}

?>