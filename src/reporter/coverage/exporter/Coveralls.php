<?php
/**
 * Kahlan: PHP Testing Framework
 *
 * @copyright     Copyright 2013, CrysaLEAD
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace kahlan\reporter\coverage\exporter;

class Coveralls {

	/**
	 * Write a coverage to an ouput file.
	 *
	 * @param  array   $options The option where the possible values are:
	 *                 -`'coverage'` The coverage instance.
	 *                 -`'file'` The output file name.
	 * @return boolean
	 */
	public static function write($options) {
		$defaults = [
			'coverage' => null,
			'file' => null
		];
		$options += $defaults;

		if (!$file = $options['file']) {
			throw new RuntimeException("Missing file name");
		}
		unset($options['file']);
		return file_put_contents($file, static::export($options));
	}

	/**
	 * Export a coverage to a string.
	 *
	 * @param  array   $options The option array where the possible values are:
	 *                 -`'coverage'` The coverage instance.
	 *                 -`'service_name'` The name of the service.
	 *                 -`'service_job_id'` The job id of the service.
	 *                 -`'repo_token'` The Coveralls repo token
	 *                 -`'run_at'` The date of a timestamp.
	 * @return boolean
	 */
	public static function export($options) {
		$defaults = [
			'coverage' => null,
			'service_name' => '',
			'service_job_id' => null,
			'repo_token' => null,
			'run_at' => date('Y-m-d H:i:s O')
		];
		$options += $defaults;

		$coverage = $options['coverage'];

		$result = $options;
		unset($result['coverage']);

		foreach ($coverage->export() as $file => $data) {
			$nbLines = count(file($file));

			$lines = [];
			for ($i = 0; $i < $nbLines; $i++) {
				$lines[] = isset($data[$i]) ? $data[$i] : null;
			}

			$result['source_files'][] = [
				'name' => $file,
				'source' => file_get_contents($file),
				'coverage' => $lines
			];
		}

		return json_encode($result);
	}
}

?>