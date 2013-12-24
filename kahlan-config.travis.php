<?php
use kahlan\reporter\coverage\exporter\Coveralls;

$this->args('coverage', 1);
$this->args('coverage-scrutinizer', 'scrutinizer.xml');
$this->args('coverage-coveralls', 'coveralls.json');

$this->applyFilter('postProcess', function($chain) {
	$coverage = $this->reporters()->get('coverage');
	if (!$coverage || !$this->args('coverage-coveralls')) {
		return $chain->next();
	}
	Coveralls::write([
		'coverage' => $coverage,
		'file' => $this->args('coverage-coveralls'),
		'service_name' => 'travis-ci',
		'service_job_id' => getenv('TRAVIS_JOB_ID') ?: null
	]);
	return $chain->next();
});

?>