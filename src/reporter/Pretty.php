<?php
namespace kahlan\reporter;

use set\Set;
use string\String;
use kahlan\analysis\Debugger;

class Pretty extends Terminal
{
    protected static $_report       = null;
    protected static $_skip         = false;
    protected static $_structure    = [];

    public function pass($report = [])
    {
        $this->_report($report);
    }

    public function skip($report = [])
    {
        $this->_report($report);
    }

    public function fail($report = [])
    {
        $this->_report($report);
    }

    public function exception($report = [])
    {
        $this->_report($report);
    }

    public function incomplete($report = [])
    {
        $this->_report($report);
    }

    public function _report($report)
    {
        $messages = $report['messages'];

        if (self::$_report === null) {
            self::$_report = $messages;
        } elseif (self::$_report != $messages) {
            if (!static::$_skip) {
                $this->_flush();
            }

            self::$_report = $messages;
            self::$_skip = false;
        }

        // We must traverse current message
        $messageTrace = array_slice($report['messages'], 0, -1);

        foreach($messageTrace as $key => $message) {
            // Generate a unique report message hash
            $trace = md5(implode('-', array_values(array_slice($messageTrace, 0, $key+1))));

            // If we have it in structure we must skip
            if (isset(static::$_structure[$trace])) continue;

            // If not, let's show it to user
            $this->write(str_repeat('  ', $key));
            $this->write($message . "\n", 'b;');

            // Store to avoid duplicates
            static::$_structure[$trace] = true;
        }

        if (!static::$_skip) {
            switch($report['type']) {
                case "fail":
                    $this->write(str_repeat('  ', $this->_getReportOffset()));
                    $this->write("✘", 'red');
                    $this->write(" ");
                    $this->write($this->_getReportTask() . "\n", 'red');

                    // Actual fetch
                    $this->write(str_repeat('  ', $this->_getReportOffset() + 1));
                    $this->write("actual:\n", 'red');

                    $this->write(String::toString($report['params']['actual']), 'n;;91');
                    $this->write("\n", "black");

                    // Excpected fetch
                    $this->write(str_repeat('  ', $this->_getReportOffset() + 1));
                    $this->write("expected:\n", 'green');
                    $this->write(String::toString($report['params']['expected']), 'n;;92');
                    $this->write("\n");

                    static::$_skip = true;
                    break;
                case "exception":
                    $this->write(str_repeat('  ', $this->_getReportOffset()));
                    $this->write("✘", 'red');
                    $this->write(" ");
                    $this->write($this->_getReportTask() . "\n", 'red');

                    $this->write(str_repeat('  ', $this->_getReportOffset() + 1));
                    $this->write("Exception: ", 'red');
                    $this->write(String::toString($report['exception']), "b;red");
                    $this->write("\n");
                    $this->write(str_repeat('  ', $this->_getReportOffset() + 1));
                    $this->write("Trace: ", 'yellow');
                    $this->write("\n");
                    foreach(explode("\n", Debugger::trace(['trace' => $report['backtrace']])) as $msg) {
                        $this->write(str_repeat('  ', $this->_getReportOffset() + 2));
                        $this->write($msg . "\n", 'd;');
                    }
                    $this->write("\n");
                    break;
                case "skip":
                    $this->write(str_repeat('  ', $this->_getReportOffset()));
                    $this->write("→", 'magenta');
                    $this->write(" ");
                    $this->write($this->_getReportTask() . "\n", 'd;magenta');

                    static::$_skip = true;
                    break;
                default:

                    break;
            }
        }
    }

    public function _flush()
    {
        $this->write(str_repeat('  ', $this->_getReportOffset()));
        $this->write("✔", 'green');
        $this->write(" ");
        $this->write($this->_getReportTask() . "\n", 'd;black');

        self::$_report = null;
    }

    protected function _getReportOffset()
    {
        return (count(static::$_report) - 1);
    }

    protected function _getReportTask()
    {
        return current(array_slice(static::$_report, -1, 1));
    }

    public function end($results = [])
    {
        $this->_flush();
        $this->write("\n");
        $this->_summary($results);
        $this->_focused($results);
    }
}
