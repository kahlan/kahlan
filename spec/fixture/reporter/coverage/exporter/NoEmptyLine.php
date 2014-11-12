<?php
namespace spec\fixture\reporter\coverage\exporter;

class NoEmptyLine
{
	public function shallNotPass()
	{
		$shallNotPass = false;
		if (false) {
			$shallNotPass = true;
		}
		return $shallNotPass;
		$shallNotPass = true;
	}
}