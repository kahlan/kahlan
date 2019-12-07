<?php

namespace Kahlan\Reporter;

class Tree extends Terminal
{
    /**
     * Message counter for a specific suite.
     *
     * @var int
     */
    protected $_count = 0;

    /**
     * The console indentation.
     *
     * @var int
     */
    protected $_indent = 0;

    /**
     * The tree pipe, used to replace the indentation.
     *
     * ### Format
     * ```php
     * {PIPE}{BRANCH}{$message}
     * ```
     *
     * ### Output Example
     * ```
     * ├── UnionTypes
     * │  ├── ::assertTypes(string ...$types): void
     * │  │  ├── UnionTypes::assertTypes('NULL')
     * ```
     *
     * @var string
     */
    const PIPE = '│  ';

    /**
     * The tree branch, used only for desciption messages.
     *
     * ### Format
     * ```php
     * {PIPE}{BRANCH}{$message}
     * ```
     *
     * ### Output Example
     * ```
     * ├── UnionTypes
     * │  ├── ::assertTypes(string ...$types): void
     * │  │  ├── UnionTypes::assertTypes('NULL')
     * ```
     *
     * @var string
     */
    const BRANCH = '├── ';

    /**
     * The spec message separator, used to separate the symbol from the message.
     *
     * ### Format
     * ```php
     * {PIPE}{$symbol}{SPEC_MESSAGE_SEPARATOR}{$specMessage}
     * ```
     *
     * ### Output Example
     * ```
     * ├── UnionTypes
     * │  ├── ::assertTypes(string ...$types): void
     * │  │  ├── UnionTypes::assertTypes('NULL')
     * │  │  ✓   it should return 'null'
     * ```
     */
    const SPEC_MESSAGE_SEPARATOR = '   ';

    /**
     * Callback called before any specs processing.
     *
     * ### Output Example
     * ```
     *              _     _
     *    /\ /\__ _| |__ | | __ _ _ __
     *   / //_/ _` | '_ \| |/ _` | '_ \
     *  / __ \ (_| | | | | | (_| | | | |
     *  \/  \/\__,_|_| |_|_|\__,_|_| |_|
     *
     *  The PHP Test Framework for Freedom, Truth and Justice.
     *
     *  src directory  : /php-union-types/src
     *  spec directory : /php-union-types/spec
     *
     *  Spec Tree:
     * ```
     *
     * @param array $args The suite arguments.
     * @return void
     */
    public function start($args)
    {
        parent::start($args);

        $this->_writeNewLine();
        $this->write("Spec Tree:", 'blue');
        $this->_writeNewLine();
    }

    /**
     * Callback called on a suite start.
     *
     * ### Format
     * ```php
     * {PIPE}{BRANCH}{$message}
     * ```
     *
     * ### Output Example
     * ```
     * ├── UnionTypes
     * ```
     *
     * @param object|null $suite The suite instance.
     * @return void
     */
    public function suiteStart($suite = null)
    {
        if ($suite === null) {
            return;
        }

        $messages = $suite->messages();
        $this->_count = count($messages);

        if ($this->_count === 1) {
            return;
        }

        $message = end($messages);
        $pipes = str_repeat(self::PIPE, $this->_count - 2);

        $this->write($pipes . self::BRANCH, 'dark-grey');
        $this->write($message);
        $this->_writeNewLine();
    }

    /**
     * Callback called after a spec execution.
     *
     * ### Format
     * ```php
     * {PIPE}{$symbol}{SPEC_MESSAGE_SEPARATOR}{$specMessage}
     * ```
     *
     * ### Output Example
     * ```
     * │  │  ✖   it should return 'int'
     * ```
     *
     * @param \Kahlan\Log $log The log object of the whole spec.
     * @return void
     */
    public function specEnd($log = null)
    {
        if ($log === null) {
            return;
        }

        $pipes = str_repeat(self::PIPE, $this->_count - 2);

        $this->write($pipes, 'dark-grey');
        $this->_reportSpecMessage($log);
        $this->_writeNewLine();
    }

    /**
     * Callback called at the end of specs processing.
     *
     * ### Output Example
     * ```
     *   Failure Tree(2):
     *  ├── UnionTypes
     *  │  ├── ::getType(mixed $value): string
     *  │  │  ├── UnionTypes::getType(1)
     *  │  │  ✖   it should return 'int'
     *    expect->toBe() failed in `./spec/UnionTypes.spec.php` line 110
     *
     *    It expect actual to be identical to expected (===).
     *
     *    actual:
     *      (string) "int"
     *    expected:
     *      (string) "integer"
     *
     *  ├── UnionTypes
     *  │  ├── ::stringify(mixed $value): string
     *  │  │  ├── UnionTypes::stringify(1.2)
     *  │  │  ✖   it should return '1.2'
     *    expect->toBe() failed in `./spec/UnionTypes.spec.php` line 189
     *
     *    It expect actual to be identical to expected (===).
     *
     *    actual:
     *      (string) "1.2"
     *    expected:
     *      (double) 1.2
     *
     *
     *  Expectations   : 8 Executed
     *  Specifications : 0 Pending, 0 Excluded, 0 Skipped
     *
     *  Passed 6 of 8 FAIL (FAILURE: 2) in 0.106 seconds (using 2MB)
     * ```
     *
     * @param \Kahlan\Summary $summary The execution summary instance.
     * @return void
     */
    public function end($summary)
    {
        $this->_writeNewLine();
        $this->_reportSkipped($summary);

        $failuresLog = [];
        foreach ($summary->logs() as $log) {
            if ($log->passed()) {
                continue;
            }

            $failuresLog[] = $log;
        }

        $failuresCount = count($failuresLog);

        if ($failuresCount) {
            $this->write("Failure Tree($failuresCount):", 'red');
            $this->_writeNewLine();
            foreach ($failuresLog as $failureLog) {
                $this->_reportFailureTree($failureLog);
            }
        }

        $this->_writeNewLine();
        $this->_reportSummary($summary);
    }

    /**
     * Print an expectation report.
     *
     * ### Output Example
     * ```
     *  ├── UnionTypes
     *  │  ├── ::getType(mixed $value): string
     *  │  │  ├── UnionTypes::getType(1)
     *  │  │  ✖   it should return 'int'
     *    expect->toBe() failed in `./spec/UnionTypes.spec.php` line 110
     *
     *    It expect actual to be identical to expected (===).
     *
     *    actual:
     *      (string) "int"
     *    expected:
     *      (string) "integer"
     * ```
     *
     * @param \Kahlan\Log $log The Log instance.
     * @return void
     */
    protected function _reportFailureTree($log)
    {
        $messages = array_values(array_filter($log->messages()));
        $failureMessage = array_pop($messages);
        foreach ($messages as $index => $message) {
            $messagePipes = str_repeat(self::PIPE, $index);

            $this->write($messagePipes . self::BRANCH, 'dark-grey');
            $this->write($message);
            $this->_writeNewLine();
        }

        $failureMessagePipes = str_repeat(self::PIPE, count($messages) - 1);

        $this->write($failureMessagePipes, 'dark-grey');
        $this->_writeSpecMessage('err', 'red', $failureMessage, 'red');
        $this->_writeNewLine();

        $this->_reportFailure($log);
    }

    /**
     * Print a spec message report using the log instance.
     *
     * ### Format
     * ```php
     * {$symbol}{SPEC_MESSAGE_SEPARATOR}{$specMessage}
     * ```
     *
     * ### Output Examples
     * Failed:
     * ```
     * ✖   it should return 'int'
     * ```
     * Passed:
     * ```
     * ✓   it should return '1'
     * ```
     *
     * @param \Kahlan\Log $log A spec log instance.
     * @return void
     */
    protected function _reportSpecMessage($log)
    {
        $messages = $log->messages();
        $message = end($messages);

        switch ($log->type()) {
            case 'passed':
                $this->_writeSpecMessage('ok', 'light-green', $message, 'dark-grey');
                break;
            case 'skipped':
                $this->_writeSpecMessage('ok', 'light-grey', $message, 'light-grey');
                break;
            case 'pending':
                $this->_writeSpecMessage('ok', 'cyan', $message, 'cyan');
                break;
            case 'excluded':
                $this->_writeSpecMessage('ok', 'yellow', $message, 'yellow');
                break;
            case 'failed':
                $this->_writeSpecMessage('err', 'red', $message, 'red');
                break;
            case 'errored':
                $this->_writeSpecMessage('err', 'red', $message, 'red');
                break;
        }
    }

    /**
     * Print a spec message.
     *
     * ### Format
     * ```php
     * {$symbol}{SPEC_MESSAGE_SEPARATOR}{$specMessage}
     * ```
     *
     * ### Output Examples
     * Failed:
     * ```
     * ✖   it should return 'int'
     * ```
     * Passed:
     * ```
     * ✓   it should return '1'
     * ```
     *
     * @param string $symbol The symbol name, see `Terminal::_symbols`, e.g. `ok`, `err`, `dot`.
     * @param string $symbolColor The symbol color, e.g. `green`, `red`, `blue`, `yellow`, `light-grey`, `dark-grey`.
     * @param string $message The spec message, e.g. `'it should return int'`.
     * @param string $messageColor The message color, e.g. `green`, `red`, `blue`, `yellow`, `light-grey`, `dark-grey`.
     * @return void
     */
    protected function _writeSpecMessage($symbol, $symbolColor, $message, $messageColor)
    {
        $this->write($this->_symbols[$symbol], $symbolColor);
        $this->write(self::SPEC_MESSAGE_SEPARATOR);
        $this->write($message, $messageColor);
    }

    /**
     * Print a new line.
     *
     * @return void
     */
    protected function _writeNewLine()
    {
        $this->write("\n");
    }

    /**
     * Set the `_count` value.
     *
     * @param int $count The new count value.
     * @return $this
     */
    public function setCount($count)
    {
        $this->_count = $count;

        return $this;
    }
}
