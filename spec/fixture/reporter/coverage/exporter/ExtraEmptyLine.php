<?php
namespace spec\fixture\reporter\coverage\exporter;

class ExtraEmptyLine
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
