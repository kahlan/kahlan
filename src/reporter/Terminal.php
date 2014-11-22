<?php
namespace kahlan\reporter;

use string\String;
use kahlan\cli\Cli;
use kahlan\analysis\Debugger;

class Terminal extends Reporter
{
    /**
     * Use colors in console mode
     *
     * @var boolean
     */
    protected $_colors = true;

    /**
     * Output stream, STDOUT
     *
     * @var stream
     */
    protected $_output = null;

    /**
     * Reporter constructor
     *
     * @param array $options.
     */
    public function __construct($options = [])
    {
        parent::__construct($options);
        $defaults = [
            'colors' => true,
            'output' => fopen('php://output', 'r')
        ];
        $options += $defaults;
        $this->_output = $options['output'];
        $this->_colors = $options['colors'];
    }

    /**
     * Print a string to STDOUT.
     *
     * @param mixed        $string  The string to print.
     * @param string|array $options The possible values for an array are:
     *                     - `'style`: a style code.
     *                     - `'color'`: a color code.
     *                     - `'background'`: a background color code.
     *                     The string must respect one of the following format:
     *                     - `'style;color;background'`
     *                     - `'style;color'`
     *                     - `'color'`
     *
     */
    public function write($string, $options = null)
    {
        $string = $this->_colors ? Cli::color($string, $options) : $string;
        fwrite($this->_output, $string);
    }

    /**
     * Callback called before any specs processing.
     *
     * @param array $params The suite params array.
     */
    public function begin($params)
    {
        parent::begin($params);
        $this->write("\n");
        $this->write("Kahlan - PHP Testing Framework\n" , 'green');
        $this->write("\nWorking Directory: ", 'blue');
        $this->write(getcwd() . "\n\n");
    }

    /**
     * Print a report to STDOUT
     *
     * @param array $report A report array.
     */
    protected function _report($report)
    {
        switch($report['type']) {
            case 'fail':
                $this->_reportFailure($report);
            break;
            case 'incomplete':
                $this->_reportIncomplete($report);
            break;
            case 'exception':
                $this->_reportException($report);
            break;
        }
    }

    /**
     * Print a failure report to STDOUT
     *
     * @param array $report A report array.
     */
    protected function _reportFailure($report)
    {
        $this->write("[Failure] ", "n;red");
        $this->_messages($report['messages']);
        $this->_reportDescription($report);
        $this->write("Trace:", "n;yellow");
        $this->write("\n");
        $this->write(Debugger::trace([
            'trace' => $report['exception'], 'depth' => 1
        ]));
        $this->write("\n\n");
    }

    /**
     * Report a description of a spec
     *
     * @param array $report A report array.
     */
    protected function _reportDescription($report)
    {
        $not = $report['not'];
        $description = $report['description'];
        if (is_array($description)) {
            $params = $description['params'];
            $description = $description['description'];
        } else {
            $params = $report['params'];
        }
        foreach ($params as $key => $value) {
            $this->write("{$key}: ", 'n;yellow');
            $type = gettype($value);
			$toString = function($instance) {
                return 'an instance of `' . get_class($instance) . '`';
            };
            $this->write("({$type}) " . String::toString($value, ['object' => ['method' => $toString]]) . "\n");
        }
        $this->write("Description:", "n;magenta");
        $this->write(" {$report['matcher']} expected actual to ");
        if ($not) {
            $this->write("NOT ", 'n;magenta');
        }
        $this->write("{$description}\n");
    }

    /**
     * Print an incomplete exception report to STDOUT
     *
     * @param array $report A report array.
     */
    protected function _reportIncomplete($report)
    {
        $this->write("[Incomplete test] ", "n;yellow");
        $this->_messages($report['messages']);
        $this->write("Description:", "n;magenta");
        $this->write(" " . Debugger::message($report['exception']) ."\n");
        $this->write("Trace:", "n;yellow");
        $this->write("\n");
        $this->write(Debugger::trace([
            'trace' => $report['exception'], 'start' => 1, 'depth' => 1
        ]));
        $this->write("\n\n");
    }

    /**
     * Print an exception report to STDOUT
     *
     * @param array $report A report array.
     */
    protected function _reportException($report)
    {
        $this->write("[Uncatched Exception] ", "n;magenta");
        $this->_messages($report['messages']);
        $this->write("Description:", "n;magenta");
        $this->write(" " . String::toString($report['exception']) ."\n");
        $this->write("Trace:", "n;yellow");
        $this->write("\n");
        $this->write(Debugger::trace(['trace' => $report['exception']]));
        $this->write("\n\n");
    }

    /**
     * Print an array of description messages to STDOUT
     *
     * @param array $messages An array of description message.
     */
    protected function _messages($messages)
    {
        $tab = 0;
        foreach ($messages as $message) {
            $this->write(str_repeat("    ", $tab));
            preg_match('/^((?:it|when)?\s*(?:not)?)(.*)$/', $message, $matches);
            $this->write($matches[1], "n;magenta");
            $this->write($matches[2]);
            $this->write("\n");
            $tab++;
        }
        $this->write("\n");
    }

    /**
     * Print a summary of specs execution to STDOUT
     *
     * @param array $results The results array of the execution.
     */
    public function _summary($report)
    {
        $results = $report['specs'];

        $passed = count($results['passed']) + count($results['skipped']);
        $failed = 0;
        foreach ([
            'exceptions' => 'exception',
            'incomplete' => 'incomplete',
            'failed' => 'fail'
        ] as $key => $value) {
            ${$value} = count($results[$key]);
            $failed += ${$value};
        }
        $total = $passed + $failed;

        $this->write('Executed ' . $passed . " of {$total} ");

        if ($failed) {
            $this->write("FAIL ", "red");
            $this->write("(");
            $comma = false;
            if ($fail) {
                $this->write("FAILURE: " . $fail , "red");
                $comma = true;
            }
            if ($incomplete) {
                if ($comma) {
                    $this->write(", ");
                }
                $this->write("INCOMPLETE: " . $incomplete , "yellow");
                $comma = true;
            }
            if ($exception) {
                if ($comma) {
                    $this->write(", ");
                }
                $this->write("EXCEPTION: " . $exception , "magenta");
            }
            $this->write(")");
        } else {
            $this->write("PASS", "green");
        }
        $time = number_format(microtime(true) - $this->_start, 3);
        $this->write(" in {$time} seconds\n\n\n");
    }

    /**
     * Print exclusive report to STDOUT
     *
     * @param array $report A report array.
     */
    protected function _exclusive($report)
    {
        if (!$backtrace = $report['exclusives']) {
            return;
        }
        $this->write("Exclusive Mode Detected in the following files:\n", "yellow");
        foreach ($backtrace as $trace) {

            $this->write(Debugger::trace(['trace' => $trace, 'start' => 1, 'depth' => 1]) . "\n");
        }
        $this->write("exit(-1)\n", "red");
    }

    /**
     * Destructor
     */
    public function __destruct()
    {
        if ($this->_output) {
            fclose($this->_output);
        }
    }
}
