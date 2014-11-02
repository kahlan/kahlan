<?php
namespace kahlan\reporter\coverage\exporter;

use DOMDocument;
use RuntimeException;

class Scrutinizer
{
    /**
     * Write a coverage to an ouput file.
     *
     * @param  array   $options The option where the possible values are:
     *                 -`'coverage'` The coverage instance.
     *                 -`'file'` The output file name.
     * @return boolean
     */
    public static function write($options)
    {
        $defaults = [
            'coverage' => null,
            'file' => null
        ];
        $options += $defaults;

        if (!$options['file']) {
            throw new RuntimeException("Missing file name");
        }

        return file_put_contents($options['file'], static::export($options));
    }

    /**
     * Export a coverage to a string.
     *
     * @param  array   $options The option array where the possible values are:
     *                 -`'coverage'` The coverage instance.
     * @return boolean
     */
    public static function export($options)
    {
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

    /**
     * Export the coverage of a file.
     *
     * @param  array   $options The option array where the possible values are:
     *                 -`'coverage'` The coverage instance.
     * @return object  the XML file node.
     */
    protected static function _exportFile($xmlDocument, $file, $data)
    {
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

    /**
     * Export the coverage of a metrics.
     *
     * @param  DOMDocument $xmlDocument The XML root node.
     * @return object      the XML file node.
     */
    protected static function _exportMetrics($xmlDocument, $metrics)
    {
        $data = $metrics->data();
        $xmlMetrics = $xmlDocument->createElement('metrics');
        $xmlMetrics->setAttribute('loc', $data['loc']);
        $xmlMetrics->setAttribute('ncloc', $data['ncloc']);
        $xmlMetrics->setAttribute('statements', $data['eloc']);
        $xmlMetrics->setAttribute('coveredstatements', $data['covered']);
        return $xmlMetrics;
    }
}