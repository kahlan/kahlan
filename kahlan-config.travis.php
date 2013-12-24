<?php
use kahlan\reporter\coverage\exporter\Coveralls;

$this->applyFilter('postProcess', function($chain, $suite, $reporter, $options) {
	$coverage = $reporter->get('coverage');
	if (!$coverage || !isset($options['coverage-coveralls'])) {
		return $chain->next();
	}
	Coveralls::write([
		'coverage' => $coverage,
		'file' => $options['coverage-coveralls'],
		'service_name' => 'travis-ci',
		'service_job_id' => getenv('TRAVIS_JOB_ID') ?: null
	]);
	return $chain->next();
});

?>