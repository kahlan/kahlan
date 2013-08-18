<?php
/**
 * Kahlan: PHP Testing Framework
 *
 * @copyright     Copyright 2013, CrysaLEAD & Craftsmen
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace kahlan\reporter\coverage\exporter;

use DOMDocument;
use RuntimeException;

class Scrutinizer {

	public static function write($options) {
		$defaults = ['file' => null];
		$options += $defaults;

		if (!$options['file']) {
			throw new RuntimeException("Missing file name");
		}

		return file_put_contents($options['file'], static::export($options));
	}

	public static function export($options) {
		$defaults = ['coverage' => null];
		$options += $defaults;
		$coverage = $options['coverage'];

		$xmlDocument = new DOMDocument('1.0', 'UTF-8');
		$xmlDocument->formatOutput = true;

		$xmlCoverage = $xmlDocument->createElement('coverage');
		$xmlCoverage->setAttribute('generated', time());
		$xmlDocument->appendChild($xmlCoverage);

		$xmlProject = $xmlDocument->createElement('project');
		$xmlProject->setAttribute('timestamp', time());
		$xmlCoverage->appendChild($xmlProject);

		foreach ($coverage->export() as $file => $data) {
			$xmlProject->appendChild(static::_exportFile($xmlDocument, $file, $data));
		}
		$xmlProject->appendChild(static::_exportMetrics($xmlDocument, $coverage->metrics()));
		return $xmlDocument->saveXML();
	}

	protected static function _exportFile($xmlDocument, $file, $data) {
		$xmlFile = $xmlDocument->createElement('file');
		$xmlFile->setAttribute('name', $file);
		foreach ($data as $line => $node) {
			$xmlLine = $xmlDocument->createElement('line');
			$xmlLine->setAttribute('num', $line + 1);
			$xmlLine->setAttribute('type', 'stmt');
			$xmlLine->setAttribute('count', $data[$line]);
			$xmlFile->appendChild($xmlLine);
		}
		return $xmlFile;
	}

	protected static function _exportMetrics($xmlDocument, $metrics) {
		$data = $metrics->get();
		$xmlMetrics = $xmlDocument->createElement('metrics');
		$xmlMetrics->setAttribute('loc', $data['loc']);
		$xmlMetrics->setAttribute('ncloc', $data['ncloc']);
		$xmlMetrics->setAttribute('statements', $data['eloc']);
		$xmlMetrics->setAttribute('coveredstatements', $data['covered']);
		return $xmlMetrics;
	}
}