<?php
/**
 * Kahlan: PHP Testing Framework
 *
 * @copyright     Copyright 2013, CrysaLEAD & Craftsmen
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace kahlan\reporter\coverage\exporter;

class Coveralls {

	public static function write($options) {
		$defaults = [
			'coverage' => ['files' => []],
			'file' => null
		];
		$options += $defaults;

		if (!$file = $options['file']) {
			throw new RuntimeException("Missing file name");
		}
		unset($options['file']);
		return file_put_contents($file, static::export($options));
	}

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