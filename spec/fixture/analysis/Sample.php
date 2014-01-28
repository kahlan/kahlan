<?php
/**
 * Some comments
 */

namespace spec\fixture\analysis;

use kahlan\A;
use kahlan\B, kahlan\C;
use kahlan\E as F;
use stdObj;

function slice($data, $keys) {
	$removed = array_intersect_key($data, array_fill_keys((array) $keys, true));
	$data = array_diff_key($data, $removed);
	return array($data, $removed);
}

class Sample extends \kahlan\fixture\Parent {

	// Using a tab
	protected	$_public = true;

	protected $_variable = true;

	public function bracketInString() {
		"/^({$pattern})/";
	}

	public function method1($a, $b = array(), $c = [], $d = 0, $f = 'hello') {
	}

	public function method2(
		$a,
		$b = array(),
		$c = [],
		$d = 0,
		$f = 'hello')
	{
		return rand($a * ($d + 1));
	}

	abstract public function abstractMethod();

	final public function finalMethod() {}

	public function inlineComment() {
	} // end function
}

class
	Sample2
	extends Sample2 {
}

interface Template1
{
    public function setVariable($name, $var);
    public function getHtml($template);
}

trait Template2 {
    public function setVariable($name, $var) {

    }
    public function getHtml($template) {

    }
}

class Dir extends \FilterIterator{
}

//No scope
for($i = 0; $i <= 10; $i++) {
	$rand = rand();
}

?>

<i> Hello World </i>

<?php
/**
 * Some comments2
 */

namespace kahlan\spec\fixture\parser;

class Sample3 extends Sample2 {
	public function myMethod() {
		return 'Hello World';
	}
}

?>
