<?php
namespace Kahlan\Reporter;

class Json extends Terminal
{
    /**
     * Store the current number of dots.
     *
     * @var integer
     */
    protected $_counter = 0;

    /**
     * Store schema for JSON output
     *
     * @var array
     */
    protected $_json = [
        'errors'  => [],
        'summary' => [
            'success'    => 0,
            'failed'     => 0,
            'skipped'    => 0,
            'error'      => 0,
            'passed'     => 0,
            'incomplete' => 0
        ]
    ];

    /**
     * Callback called before any specs processing.
     *
     * @param array $params The suite params array.
     */
    public function start($params)
    {
        $this->_header = false;
        parent::start($params);
    }

    /**
     * Callback called on successful expect.
     *
     * @param object $report An expect report object.
     */
    public function pass($report = null)
    {
        $this->_json['summary']['passed'] += 1;
    }

    /**
     * Callback called on failure.
     *
     * @param object $report An expect report object.
     */
    public function fail($report = null)
    {
        $this->_json['summary']['failed'] += 1;
    }

    /**
     * Callback called when an exception occur.
     *
     * @param object $report An expect report object.
     */
    public function exception($report = null)
    {
        $this->_json['summary']['failed'] += 1;
    }

    /**
     * Callback called on a skipped spec.
     *
     * @param object $report An expect report object.
     */
    public function skip($report = null)
    {
        $this->_json['summary']['skipped'] += 1;
    }

    /**
     * Callback called when a `Kahlan\IncompleteException` occur.
     *
     * @param object $report An expect report object.
     */
    public function incomplete($report = null)
    {
        $this->_json['summary']['incomplete'] += 1;
    }

    /**
     * Callback called at the end of specs processing.
     *
     * @param array $results The results array of the execution.
     */
    public function end($results = [])
    {
        foreach ($results['specs'] as $type => $reports) {
            foreach ($reports as $report) {
                if ($report->type() !== 'pass' && $report->type() !== 'skip') {
                    switch ($report->type()) {
                        case 'fail':
                            $this->_json['errors'][] = [
                                'spec' => trim(implode(' ', $report->messages())),
                                'suite' => $report->file(),
                                'actual' => $report->params()['actual'],
                                'expected' => $report->params()['expected']
                            ];
                        break;
                        case 'exception':
                            $exception = $report->exception();

                            $this->_json['errors'][] = [
                                'spec' => trim(implode(' ', $report->messages())),
                                'suite' => $report->file(),
                                'exception' => '`' . get_class($exception) .'` Code(' . $exception->getCode() . ')',
                                'trace' => $exception->getMessage()
                            ];
                        break;
                    }

                }
            }
        }
        $this->write(json_encode($this->_json));
    }
}