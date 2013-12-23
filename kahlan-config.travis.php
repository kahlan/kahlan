<?php
use kahlan\reporter\coverage\exporter\Coveralls;

$this->applyFilter('_postProcess', function($chain, $suite, $reporter, $options) {
	$coverage = $reporter->get('coverage');

	if (!$coverage) {
		return;
	}
	if (isset($options['coverage-coveralls'])) {
		Coveralls::write([
			'coverage' => $coverage,
			'file' => $options['coverage-coveralls'],
			'service_name' => 'travis-ci',
			'service_job_id' => getenv('TRAVIS_JOB_ID') ?: null
		]);
	}
	return $chain->next();
});

?>