<?php
namespace kahlan\reporter;

use kahlan\util\Text;
use kahlan\cli\Cli;
use kahlan\analysis\Debugger;

class Terminal extends Reporter
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
     * Indicates if the header can be displayed.
     *
     * @var boolean
     */
    protected $_header = true;

    /**
     * Indicates if colors will be used.
     *
     * @var boolean
     */
    protected $_colors = true;

    /**
     * The console to output stream on (e.g STDOUT).
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
            'colors' => true,
            'header' => true,
            'output' => fopen('php://output', 'r')
        ];
        $config += $defaults;

        $this->_colors = $config['colors'];
        $this->_header = $config['header'];
        $this->_output = $config['output'];
    }

    /**
     * Callback called before any specs processing.
     *
     * @param array $params The suite params array.
     */
    public function start($params)
    {
        parent::start($params);
        if (!$this->_header) {
            return;
        }
        $this->write($this->kahlan() . "\n\n");
        $this->write($this->kahlanBaseline() . "\n", 'd');
        $this->write("\nWorking Directory: ", 'blue');
        $this->write(getcwd() . "\n");
    }

    /**
     * Returns the Kahlan ascii art string.
     *
     * @return string
     */
    public function kahlan()
    {
        return <<<EOD
            _     _
  /\ /\__ _| |__ | | __ _ _ __
 / //_/ _` | '_ \| |/ _` | '_ \
/ __ \ (_| | | | | | (_| | | | |
\/  \/\__,_|_| |_|_|\__,_|_| |_|
EOD;
    }

    /**
     * Returns the Kahlan baseline string.
     *
     * @return string
     */
    public function kahlanBaseline()
    {
        return "The Unit/BDD PHP Test Framework for Freedom, Truth, and Justice.";
    }

    /**
     * Prints a spec report with its parents messages.
     *
     * @param object $report A spec report instance.
     */
    protected function _report($report)
    {
        $this->_reportSuiteMessages($report);
        $this->_reportSpecMessage($report);
        $this->_reportExpect($report);
        $this->indent(0);
    }

    /**
     * Prints an array of description messages to STDOUT
     *
     * @param  array   $messages An array of description message.
     * @return integer           The final message indentation.
     */
    protected function _reportSuiteMessages($report)
    {
        $this->_indent = 0;
        $messages = array_values(array_filter($report->messages()));
        array_pop($messages);
        foreach ($messages as $message) {
            $this->write($message);
            $this->write("\n");
            $this->_indent++;
        }
    }

    /**
     * Prints a spec report.
     *
     * @param object $report A spec report instance.
     */
    protected function _reportSpec($report)
    {
        $this->_reportSpecMessage($report);
        foreach($report->childs() as $child) {
            $this->_reportExpect($child);
        }
    }

    protected function _reportSpecMessage($report)
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
                $this->_reportDiff($report);
            break;
            case "exception":
                $this->write("an uncaught exception has been thrown in ", 'magenta');
                $this->write("`{$report->file()}` ");
                $this->write("line {$report->line()}", 'magenta');
                $this->write("\n\n");

                $this->write('message:', 'yellow');
                $this->_reportException($report->exception());
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
                $this->_reportException($report->exception());
                $this->prefix($this->format(' ', 'n;;magenta') . ' ');
                $this->write(Debugger::trace(['trace' => $report->backtrace()]));
                $this->prefix('');
                $this->write("\n\n");
            break;
        }
        $this->_indent--;
    }

    /**
     * Prints diff of spec's params.
     *
     * @param array $report A report array.
     */
    protected function _reportDiff($report)
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
            $this->write("({$type}) " . Text::toString($value, ['object' => ['method' => $toString]]));
            $this->prefix('');
            $this->write("\n");
        }
        $this->write("\n");
    }

    protected function _reportException($exception)
    {
        $msg = '`' . get_class($exception) .'` Code(' . $exception->getCode() . ') with ';
        $message = $exception->getMessage();
        if ($message) {
            $msg .= 'message '. Text::dump($exception->getMessage());
        } else {
            $msg .= 'no message';
        }
        $this->write("{$msg}\n\n");
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
            'failed'     => 'fail'
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
     * Prints focused report to STDOUT
     *
     * @param array $report A report array.
     */
    protected function _reportFocused($report)
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
     * Destructor
     */
    public function __destruct()
    {
        if ($this->_output) {
            fclose($this->_output);
        }
    }
}
