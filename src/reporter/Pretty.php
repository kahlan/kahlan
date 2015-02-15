<?php
namespace kahlan\reporter;

use set\Set;
use string\String;
use kahlan\cli\Cli;
use kahlan\analysis\Debugger;

class Pretty extends Terminal
{
    /**
     * Indicates if the console cursor in on a new line.
     *
     * @var boolean
     */
    protected $_newLine = true;

    /**
     * The console indentation.
     *
     * @var integer
     */
    protected $_indent = 0;

    /**
     * The console indentation value.
     *
     * @var string
     */
    protected $_indentValue = '  ';

    /**
     * A prefix to apply in addition of indentation.
     *
     * @var string
     */
    protected $_prefix = '';

    /**
     * Callback called on a suite start.
     *
     * @param object $report The report object of the whole spec.
     */
    public function suiteStart($report = null)
    {
        $messages = $report->messages();
        $message = end($messages);
        $this->write("{$message}\n", "b;");
        $this->_indent++;
    }

    /**
     * Callback called after a suite execution.
     *
     * @param object $report The report object of the whole spec.
     */
    public function suiteEnd($report = null)
    {
        $this->_indent--;
    }

    /**
     * Callback called after a spec execution.
     *
     * @param object $report The report object of the whole spec.
     */
    public function specEnd($report = null)
    {
        $messages = $report->messages();
        $message = end($messages);

        switch($report->type()) {
            case "pass":
                $this->write("✔", 'green');
                $this->write(" ");
                $this->write("{$message}\n", 'd');
            break;
            case "skip":
                $this->write("↩", 'cyan');
                $this->write(" ");
                $this->write("{$message}\n", 'cyan');
            break;
            case "fail":
                $this->write("✘", 'red');
                $this->write(" ");
                $this->write("{$message}\n", 'red');
            break;
            case "exception":
                $this->write("✘", 'red');
                $this->write(" ");
                $this->write("{$message}\n", 'red');
            break;
            case "incomplete":
                $this->write("✘", 'red');
                $this->write(" ");
                $this->write("{$message}\n", 'red');
            break;
        }

        foreach($report->childs() as $child) {
            $this->_reportExpect($child);
        }
    }

    /**
     * Prints an expectation report.
     *
     * @param object $report An expectation report.
     */
    protected function _reportExpect($report)
    {
        $this->_indent++;
        switch($report->type()) {
            case "skip":
                $this->write("specification skipped in ", 'cyan');
                $this->write("`{$report->file()}` ");
                $this->write("line {$report->line()}", 'cyan');
                $this->write("\n\n");
            break;
            case "fail":
                $this->write("expect->{$report->matcherName()}() failed in ", 'red');
                $this->write("`{$report->file()}` ");
                $this->write("line {$report->line()}", 'red');
                $this->write("\n\n");
                $this->_reportDescription($report);
            break;
            case "exception":
                $this->write("an uncaught exception has been thrown in ", 'magenta');
                $this->write("`{$report->file()}` ");
                $this->write("line {$report->line()}", 'magenta');
                $this->write("\n\n");

                $this->write('message:', 'yellow');
                $this->write(' ' . String::toString($report->exception()) ."\n");
                $this->prefix($this->format(' ', 'n;;magenta') . ' ');
                $this->write(Debugger::trace(['trace' => $report->backtrace()]));
                $this->prefix('');
                $this->write("\n\n");
            break;
            case "incomplete":
                $this->write("an unexisting class has been used in ", 'yellow');
                $this->write("`{$report->file()}` ");
                $this->write("line {$report->line()}", 'yellow');
                $this->write("\n\n");

                $this->write('message:', 'yellow');
                $this->write(' ' . String::toString($report->exception()) ."\n");
                $this->prefix($this->format(' ', 'n;;magenta') . ' ');
                $this->write(Debugger::trace(['trace' => $report->backtrace()]));
                $this->prefix('');
                $this->write("\n\n");
            break;
        }
        $this->_indent--;
    }

    /**
     * Prints a description of a spec
     *
     * @param array $report A report array.
     */
    protected function _reportDescription($report)
    {
        $params = $report->params();
        foreach ($params as $key => $value) {
            if (preg_match('~actual~', $key)) {
                $this->write("{$key}:\n", 'yellow');
                $this->prefix($this->format(' ', 'n;;91') . ' ');
            } elseif (preg_match('~expected~', $key)) {
                $this->write("{$key}:\n", 'yellow');
                $this->prefix($this->format(' ', 'n;;92') . ' ');
            } else {
                $this->write("{$key}:\n", 'yellow');
            }
            $type = gettype($value);
            $toString = function($instance) {
                return 'an instance of `' . get_class($instance) . '`';
            };
            $this->write("({$type}) " . String::toString($value, ['object' => ['method' => $toString]]) . "\n");
            $this->prefix('');
            $this->write("\n");
        }
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
        $indent = str_repeat($this->_indentValue, $this->indent()) . $this->prefix();

        if ($newLine = ($string && $string[strlen($string) - 1] === "\n")) {
            $string = substr($string, 0, -1);
        }

        $string = str_replace("\n", "\n" . $indent, $string) . ($newLine ? "\n" : '');

        $indent = $this->_newLine ? $indent : '';
        $this->_newLine = $newLine;

        fwrite($this->_output, $indent . $this->format($string, $options));
    }

    /**
     * Gets/sets the console indentation.
     *
     * @param  integer $indent The indent number.
     * @return integer         Returns the indent value.
     */
    public function indent($indent = null)
    {
        if ($indent === null) {
            return $this->_indent;
        }
        return $this->_indent = $indent;
    }

    /**
     * Gets/sets the console prefix to use for writing.
     *
     * @param  string $prefix The prefix.
     * @return string         Returns the prefix value.
     */
    public function prefix($prefix = null)
    {
        if ($prefix === null) {
            return $this->_prefix;
        }
        return $this->_prefix = $prefix;
    }

    /**
     * Format a string to output.
     *
     * @param string       $string  The string to format.
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
    public function format($string, $options = null)
    {
        return $this->_colors ? Cli::color($string, $options) : $string;
    }

    /**
     * Prints _ocused report to STDOUT
     *
     * @param array $report A report array.
     */
    public function _focused($report)
    {
        if (!$backtraces = $report['focuses']) {
            return;
        }

        $this->write("Focus Mode Detected in the following files:\n", 'b;yellow;');
        foreach ($backtraces as $backtrace) {
            $this->write(Debugger::trace(['trace' => $backtrace, 'depth' => 1]), 'n;yellow');
            $this->write("\n");
        }
        $this->write("exit(-1)\n\n", 'red');
    }

    /**
     * Callback called at the end of specs processing.
     */
    public function end($results = [])
    {
        $this->write("\n\n");
        $this->_summary($results);
        $this->_focused($results);
    }
}
