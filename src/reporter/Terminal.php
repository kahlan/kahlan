<?php
namespace kahlan\reporter;

use string\String;
use kahlan\cli\Cli;
use kahlan\analysis\Debugger;

class Terminal extends Reporter
{
    /**
     * Indicates if colors will be used.
     *
     * @var boolean
     */
    protected $_colors = true;

    /**
     * Output stream, STDOUT.
     *
     * @var stream
     */
    protected $_output = null;

    /**
     * The constructor.
     *
     * @param array $config The config array. Possible values are:
     *                      - `'colors' _boolean_ : If `false`, colors will be ignored.
     *                      - `'output' _resource_: The output resource.
     */
    public function __construct($config = [])
    {
        parent::__construct($config);
        $defaults = [
            'colors'  => true,
            'output'  => fopen('php://output', 'r')
        ];
        $config += $defaults;
        $this->_colors = $config['colors'];
        $this->_output = $config['output'];
    }

    /**
     * Prints a string to output.
     *
     * @param string       $string  The string to print.
     * @param string|array $options The possible values for an array are:
     *                              - `'style`: a style code.
     *                              - `'color'`: a color code.
     *                              - `'background'`: a background color code.
     *
     *                              The string must respect one of the following format:
     *                              - `'style;color;background'`
     *                              - `'style;color'`
     *                              - `'color'`
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
        $this->write(getcwd() . "\n");
    }

    /**
     * Prints a report to STDOUT
     *
     * @param array $report A report array.
     */
    protected function _report($report)
    {
        switch($report['type']) {
            case 'skip':
                $this->_reportSkipped($report);
            break;
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
     * Prints a skipped report to STDOUT
     *
     * @param array $report A report array.
     */
    protected function _reportSkipped($report)
    {
        $this->write("\n");
        $this->write('[Skipped] ', 'cyan');
        $this->write($this->_file($report) . "\n");

        $indent = $this->_messages($report['messages']);
        $trace = reset($report['backtrace']);
        $line = $trace['line'];
        $this->write(str_repeat('    ', $indent));
        $this->write(' specification');
        $this->write(' skipped', 'cyan');
        $this->write(' - ');
        $this->write("line {$line}\n", 'yellow');
    }

    /**
     * Prints a failure report to STDOUT
     *
     * @param array $report A report array.
     */
    protected function _reportFailure($report)
    {
        $this->write("\n");
        $this->write('[Failure] ', 'red');
        $this->write($this->_file($report) . "\n");

        $indent = $this->_messages($report['messages']);
        $trace = reset($report['backtrace']);
        $line = $trace['line'];
        $this->write(str_repeat('    ', $indent));
        $this->write('expect->');
        $this->write($report['matcher'], 'magenta');
        $this->write('()');
        $this->write(' failed', 'red');
        $this->write(' - ');
        $this->write("line {$line}", 'yellow');

        $this->write("\n\n");
        $this->_reportDescription($report);
        $this->write('Trace:', 'yellow');
        $this->write("\n");
        $this->write(Debugger::trace(['trace' => $report['backtrace'], 'depth' => 1]));
        $this->write("\n");
    }

    /**
     * Prints an incomplete exception report to STDOUT
     *
     * @param array $report A report array.
     */
    protected function _reportIncomplete($report)
    {
        $this->write("\n");
        $this->write('[Incomplete] ', 'yellow');
        $this->write($this->_file($report) . "\n");

        $indent = $this->_messages($report['messages']);
        $trace = reset($report['backtrace']);
        $line = $trace['line'];
        $this->write(str_repeat('    ', $indent));
        $this->write(' an unexisting');
        $this->write(' class', 'yellow');
        $this->write(" has been used");
        $this->write(' - ');
        $this->write("line {$line}", 'yellow');

        $this->write("\n\n");
        $this->write('Description:', 'magenta');
        $this->write(' ' . Debugger::message($report['exception']) ."\n");
        $this->write('Trace:', 'yellow');
        $this->write("\n");
        $this->write(Debugger::trace(['trace' => $report['backtrace']]));
        $this->write("\n");
    }

    /**
     * Print an exception report to STDOUT
     *
     * @param array $report A report array.
     */
    protected function _reportException($report)
    {
        $this->write("\n");
        $this->write('[Exception] ', 'magenta');
        $this->write($this->_file($report) . "\n");

        $indent = $this->_messages($report['messages']);
        $trace = reset($report['backtrace']);
        $line = $trace['line'];
        $this->write(str_repeat('    ', $indent));
        $this->write(' an uncaught');
        $this->write(' exception', 'magenta');
        $this->write(" has been thrown");
        $this->write(' - ');
        $this->write("line {$line}", 'yellow');

        $this->write("\n\n");
        $this->write('Description:', 'magenta');
        $this->write(' ' . String::toString($report['exception']) ."\n");
        $this->write('Trace:', 'yellow');
        $this->write("\n");
        $this->write(Debugger::trace(['trace' => $report['backtrace']]));
        $this->write("\n");
    }

    /**
     * Prints a description of a spec
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
            $this->write("{$key}: ", 'yellow');
            $type = gettype($value);
            $toString = function($instance) {
                return 'an instance of `' . get_class($instance) . '`';
            };
            $this->write("({$type}) " . String::toString($value, ['object' => ['method' => $toString]]) . "\n");
        }
        $this->write('Description:', 'magenta');
        $this->write(" {$report['matcher']} expected actual to ");
        if ($not) {
            $this->write('NOT ', 'magenta');
        }
        $this->write("{$description}\n");
    }

    /**
     * Prints an array of description messages to STDOUT
     *
     * @param  array   $messages An array of description message.
     * @return integer           The final message indentation.
     */
    protected function _messages($messages)
    {
        $indent = 0;
        $messages = array_values(array_filter($messages));
        foreach ($messages as $message) {
            $this->write(str_repeat('    ', $indent));
            preg_match('/^((?:it|when)?\s*(?:not)?)(.*)$/', $message, $matches);
            $this->write($matches[1], 'magenta');
            $this->write($matches[2]);
            $this->write("\n");
            $indent++;
        }
        return $indent;
    }

    /**
     * Extracts the file path reference of a report.
     *
     * @param  array       $report A report array.
     * @return string|null         The file path reference or `null` if no backtrace is available.
     */
    protected function _file($report) {
        if (!isset($report['backtrace'])) {
            return;
        }
        $backtrace = reset($report['backtrace']);
        $path = preg_replace('~' . getcwd() . '~', '', $backtrace['file']);
        return $path;
    }


    /**
     * Prints a summary of specs execution to STDOUT
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
            $this->write('FAIL ', 'red');
            $this->write('(');
            $comma = false;
            if ($fail) {
                $this->write('FAILURE: ' . $fail , 'red');
                $comma = true;
            }
            if ($incomplete) {
                if ($comma) {
                    $this->write(', ');
                }
                $this->write('INCOMPLETE: ' . $incomplete , 'yellow');
                $comma = true;
            }
            if ($exception) {
                if ($comma) {
                    $this->write(', ');
                }
                $this->write('EXCEPTION: ' . $exception , 'magenta');
            }
            $this->write(')');
        } else {
            $this->write('PASS', 'green');
        }
        $time = number_format(microtime(true) - $this->_start, 3);
        $this->write(" in {$time} seconds\n\n\n");
    }

    /**
     * Prints exclusive report to STDOUT
     *
     * @param array $report A report array.
     */
    protected function _exclusive($report)
    {
        if (!$backtraces = $report['exclusives']) {
            return;
        }
        $this->write("Exclusive Mode Detected in the following files:\n", 'yellow');
        foreach ($backtraces as $backtrace) {
            $this->write(Debugger::trace(['trace' => $backtrace, 'depth' => 1]) . "\n");
        }
        $this->write("exit(-1)\n", 'red');
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
