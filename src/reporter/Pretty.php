<?php
namespace kahlan\reporter;

use set\Set;
use string\String;
use kahlan\analysis\Debugger;

class Pretty extends Terminal
{
    protected static $_reports = [];

    public function after($report = null)
    {
        $messages = array_values($report->messages());

        $_report = '';
        foreach(array_slice($messages, 0, -1) as $idx => $message) {
            if (strlen($message) == 0) continue;

            $_report .= '\\' . $message;
            if (!in_array($_report, static::$_reports)) {
                static::$_reports[] = $_report;
                $this->write(str_repeat("  ", $idx));
                $this->write("{$message}\n", "b;");
            }
        }

        $check  = current(array_slice($messages, -1, 1));
        $offset = count($messages) - 1;
        switch($report->type()) {
            case "pass":
                $this->write(str_repeat('  ', $offset));
                $this->write("✔", 'green');
                $this->write(" ");
                $this->write("{$check}\n", 'd;black');
                break;
            case "fail":
                $this->write(str_repeat('  ', $offset));
                $this->write("✘", 'red');
                $this->write(" ");
                $this->write("{$check}\n", 'red');
                $this->write("\n");

                foreach($report->childs() as $child) {
                    $params = $child->params();
                    $this->write(str_repeat('  ', $offset + 1));
                    $this->write("Failed {$child->matcherName()}() on line {$child->line()} in {$child->file()}\n", 's;red');

                    // Actual
                    $actual = String::toString($params['actual']);
                    foreach(explode("\n", $actual) as $str) {
                        $this->write(str_repeat('  ', $offset + 1) . "{$str}\x1B[K", 'n;;91');
                        $this->write("\n");
                    }

                    // Expected
                    $expected = String::toString($params['expected']);
                    foreach(explode("\n", $expected) as $str) {
                        $this->write(str_repeat('  ', $offset + 1) . "{$str}\x1B[K", 'n;;92');
                        $this->write("\n");
                    }
                    $this->write("\n");
                }
                break;
            case "exception":
                $exception = $report->exception();
                $backtrace = $report->backtrace();

                $this->write(str_repeat('  ', $offset));
                $this->write("✘", 'red');
                $this->write(" ");
                $this->write("{$check}\n", 'red');
                $this->write(str_repeat('  ', $offset + 1));
                $this->write("Exception: ", 'red');
                $this->write(String::toString($exception), "b;red");
                $this->write("\n");
                $this->write(str_repeat('  ', $offset + 1));
                $this->write("Trace: ", 'yellow');
                $this->write("\n");
                foreach(explode("\n", Debugger::trace(['trace' => $backtrace])) as $msg) {
                    $this->write(str_repeat('  ', $offset + 2));
                    $this->write($msg . "\n", 'd;');
                }
                break;
            case "skip":
                $this->write(str_repeat('  ', $offset));
                $this->write("→", 'magenta');
                $this->write(" ");
                $this->write("{$check}\n", 'd;magenta');
                break;
        }
    }

    public function end($results = [])
    {
        $this->write("\n\n");
        $this->_summary($results);
        $this->_focused($results);
    }

    public function _focused($report)
    {
        if (!$backtraces = $report['focuses']) {
            return;
        }

        $this->write("Focus Mode Detected in the following files:\n", 'b;yellow;');
        foreach ($backtraces as $backtrace) {
            $this->write(Debugger::trace(['trace' => $backtrace, 'depth' => 1]), 'n;;yellow');
            $this->write("\n");
        }

        $this->write("\n\n");
    }
}
