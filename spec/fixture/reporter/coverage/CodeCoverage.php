<?php
namespace spec\fixture\reporter\coverage;

class CodeCoverage
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

	public function shallPass()
	{
		$shallPass = false;
		if (true) {
			$shallPass = true;
		}
		return $shallPass;
	}
}
