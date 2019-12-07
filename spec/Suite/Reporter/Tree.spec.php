<?php
namespace Kahlan\Spec\Suite\Reporter\Coverage;

use Kahlan\Reporter\Tree;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MultipleClasses
class Suite
{
    protected $_messages = [];

    public function __construct(array $messages)
    {
        $this->_messages = $messages;
    }

    public function messages()
    {
        return $this->_messages;
    }
}

// phpcs:ignore PSR1.Classes.ClassDeclaration.MultipleClasses
class Log
{
    protected $_type = '';
    protected $_messages = [];
    protected $_file = '';
    protected $_line = 0;
    protected $_expectations = [];
    protected $_exception = null;

    public function __construct($type, array $messages, $file = '', $line = 0, $expectations = [], $exception = null)
    {
        $this->_type = $type;
        $this->_messages = $messages;
        $this->_file = $file;
        $this->_line = $line;
        $this->_expectations = $expectations;
        $this->_exception = $exception;
    }

    public function type()
    {
        return $this->_type;
    }

    public function messages()
    {
        return $this->_messages;
    }

    public function passed()
    {
        return $this->_type !== 'failed' && $this->_type !== 'errored';
    }

    public function file()
    {
        return $this->_file;
    }

    public function line()
    {
        return $this->_line;
    }

    public function children()
    {
        return $this->_expectations;
    }

    public function exception()
    {
        return $this->_exception;
    }
}

// phpcs:ignore PSR1.Classes.ClassDeclaration.MultipleClasses
class Expectation
{
    public function __construct($type, $data, $matcherName, $file, $line, $not, $description)
    {
        $this->_type = $type;
        $this->_data = $data;
        $this->_matcherName = $matcherName;
        $this->_file = $file;
        $this->_line = $line;
        $this->_not = $not;
        $this->_description = $description;
    }

    public function type()
    {
        return $this->_type;
    }

    public function data()
    {
        return $this->_data;
    }

    public function matcherName()
    {
        return $this->_matcherName;
    }

    public function file()
    {
        return $this->_file;
    }

    public function line()
    {
        return $this->_line;
    }

    public function not()
    {
        return $this->_not;
    }

    public function description()
    {
        return $this->_description;
    }
}

// phpcs:ignore PSR1.Classes.ClassDeclaration.MultipleClasses
class Exception implements \Iterator
{
    private $position = 0;

    public function __construct($message, $code, $file, $line, $trace)
    {
        $this->position = 0;
        $this->_message = $message;
        $this->_code = $code;
        $this->_file = $file;
        $this->_line = $line;
        $this->_trace = $trace;
    }

    public function getMessage()
    {
        return $this->_message;
    }

    public function getCode()
    {
        return $this->_code;
    }

    public function getFile()
    {
        return $this->_file;
    }

    public function getLine()
    {
        return $this->_line;
    }

    public function getTrace()
    {
        return $this->_trace;
    }

    public function rewind()
    {
        $this->position = 0;
    }

    public function current()
    {
        return $this->_trace[$this->position];
    }

    public function key()
    {
        return $this->position;
    }

    public function next()
    {
        ++$this->position;
    }

    public function valid()
    {
        return isset($this->_trace[$this->position]);
    }

}

// phpcs:ignore PSR1.Classes.ClassDeclaration.MultipleClasses
class Summary
{
    protected $_logs = [];

    public function __construct($logs)
    {
        $this->_logs = $logs;
    }

    public function logs($type = null)
    {
        if ($type === null) {
            return $this->_logs;
        }

        return $this->_filterLogsByType($type);
    }

    public function passed()
    {
        return $this->_countLogsByType('passed');
    }

    public function skipped()
    {
        return $this->_countLogsByType('skipped');
    }

    public function pending()
    {
        return $this->_countLogsByType('pending');
    }

    public function excluded()
    {
        return $this->_countLogsByType('excluded');
    }

    public function failed()
    {
        return $this->_countLogsByType('failed');
    }

    public function errored()
    {
        return $this->_countLogsByType('errored');
    }

    public function expectation()
    {
        return count($this->_logs) - $this->skipped() - $this->pending() - $this->excluded();
    }

    public function executable()
    {
        return $this->passed() + $this->failed() + $this->errored();
    }

    public function memoryUsage()
    {
        return 2000000;
    }

    public function get($type)
    {
        return [];
    }

    protected function _countLogsByType($type)
    {
        return count($this->_filterLogsByType($type));
    }

    protected function _filterLogsByType($type)
    {
        return array_filter($this->_logs, function ($log) use ($type) {
            return $log->type() === $type;
        });
    }
}

describe("Tree", function () {

    $eraseFile = function ($file) {
        $file = fopen($file, 'w');
        fwrite($file, '');

        return fclose($file);
    };
    $DS = DIRECTORY_SEPARATOR;
    $REPORTER = __DIR__;
    $ROOT = realpath($REPORTER . $DS . '..' . $DS . '..' . $DS . '..');
    $SRC = $ROOT . $DS . 'src';
    $SPEC = $ROOT . $DS . 'spec';
    $TIME_PLACEHOLDER = '{time}';

    //region $start
    $start = <<<EOD
              _     _
    /\ /\__ _| |__ | | __ _ _ __
   / //_/ _` | '_ \| |/ _` | '_ \
  / __ \ (_| | | | | | (_| | | | |
  \/  \/\__,_|_| |_|_|\__,_|_| |_|
  
  The PHP Test Framework for Freedom, Truth and Justice.
  
  src directory  : $SRC
  spec directory : $SPEC
  
  Spec Tree:

EOD;
    //endregion

    //region $suiteStart
    $suiteStart = <<<EOD
  │  ├── ::assertTypes(string ...\$types): void
  │  │  ├── UnionTypes::assertTypes('NULL')
  │  │  ├── UnionTypes::assertTypes('integer')
  │  ├── ::getType(mixed \$value): string
  │  │  ├── UnionTypes::getType(1)

EOD;
    //endregion

    //region $specEnd
    $specEnd = <<<EOD
  ✓   it should throw InvalidUnionTypeException `NULL`, use `null` instead
  ✓   it should throw InvalidUnionTypeException `integer`, use `int` instead
  ✓   it should return 'int'
  ✓   it should return 'float'
  ✖   it should return 'float'
  ✖   it should return 'Cake\ORM\Table'

EOD;
    //endregion

    //region $end
    $end = <<<EOD
  
  Pending specification: 1
  ./spec/UnionTypes.spec.php, line 119
  
  Excluded specification: 1
  ./spec/UnionTypes.spec.php, line 126
  
  Skipped specification: 1
  ./spec/UnionTypes.spec.php, line 134
  
  Failure Tree(2):
  ├── UnionTypes
  │  ├── ::assertTypes(string ...\$types): void
  │  │  ├── UnionTypes::assertTypes('NULL')
  │  │  ✖   it should throw InvalidUnionTypeException `NULL`, use `null` instead
    expect->toBe() failed in `./spec/UnionTypes.spec.php` line 125
    
    It expect actual to be identical to expected (===).
    
    actual:
      (string) "string"
    expected:
      (NULL) null
    
  ├── UnionTypes
  │  ├── ::assertTypes(string ...\$types): void
  │  │  ├── UnionTypes::assertTypes('integer')
  │  │  ✖   it should throw InvalidUnionTypeException `integer`, use `int` instead
    an uncaught exception has been thrown in `./vendor/kahlan/kahlan/src/Matcher/ToBe.php` line 13
    
    message:`Kahlan\Spec\Suite\Reporter\Coverage\Exception` Code(0) with message "Too few arguments to function Kahlan\\\\Matcher\\\\ToBe::match(), 1 passed and exactly 2 expected"
    
      Kahlan\Matcher\ToBe::match() - ./vendor/kahlan/kahlan/src/Matcher/ToBe.php, line 13
      Kahlan\Expectation::_spin() - ./vendor/kahlan/kahlan/src/Expectation.php, line 212
      Kahlan\Expectation::__call() - ./spec/UnionTypes.spec.php, line 130
    
  
  Expectations   : 3 Executed
  Specifications : 1 Pending, 1 Excluded, 1 Skipped
  
  Passed 1 of 3 FAIL (FAILURE: 1, EXCEPTION: 1) in $TIME_PLACEHOLDER seconds (using 2MB)
  

EOD;
    //endregion

    describe('->start($args)', function () use ($eraseFile, $REPORTER, $DS, $SRC, $SPEC, $start) {
        $it = function () use ($eraseFile, $REPORTER, $DS, $SRC, $SPEC, $start) {
            $file = $REPORTER . $DS . 'Console' . $DS . 'start.txt';
            $eraseFile($file);

            $tree = new Tree(['colors' => false, 'output' => fopen($file, 'w'), 'src' => [$SRC], 'spec' => [$SPEC]]);
            $tree->start(['total' => 0]);

            $expect = file_get_contents($file);
            expect($expect)->toBe($start);
        };
        it("should write the `start` message to the console", $it);
    });

    describe('->suiteStart($suite = null)', function () use ($eraseFile, $REPORTER, $DS, $SRC, $SPEC, $suiteStart) {
        it('should return if `$suite === null`', function () {
            $tree = new Tree();
            $expect = $tree->suiteStart(null);
            expect($expect)->toBeNull();
        });

        $messagesSuite = [
            [
                0 => ""
            ],
            [
                0 => "",
                1 => "UnionTypes",
                2 => '::assertTypes(string ...$types): void',
            ],
            [
                0 => "",
                1 => "UnionTypes",
                2 => '::assertTypes(string ...$types): void',
                3 => "UnionTypes::assertTypes('NULL')",
            ],
            [
                0 => "",
                1 => "UnionTypes",
                2 => '::assertTypes(string ...$types): void',
                3 => "UnionTypes::assertTypes('integer')",
            ],
            [
                0 => "",
                1 => "UnionTypes",
                2 => '::getType(mixed $value): string',
            ],
            [
                0 => "",
                1 => "UnionTypes",
                2 => '::getType(mixed $value): string',
                3 => "UnionTypes::getType(1)",
            ]
        ];
        $it = function () use ($eraseFile, $REPORTER, $DS, $SRC, $SPEC, $suiteStart, $messagesSuite) {
            $file = $REPORTER . $DS . 'Console' . $DS . 'suiteStart.txt';
            $eraseFile($file);

            $tree = new Tree(['colors' => false, 'output' => fopen($file, 'w'), 'src' => [$SRC], 'spec' => [$SPEC]]);
            foreach ($messagesSuite as $messages) {
                $tree->suiteStart(new Suite($messages));
            }

            $expect = file_get_contents($file);
            expect($expect)->toBe($suiteStart);
        };
        it("should write the `suiteStart` message to the console", $it);
    });

    describe('->specEnd($log = null)', function () use ($eraseFile, $REPORTER, $DS, $SRC, $SPEC, $specEnd) {
        it('should return if `$log === null`', function () {
            $tree = new Tree();
            $expect = $tree->specEnd(null);
            expect($expect)->toBeNull();
        });

        $messagesLog = [
            [
                'type' => 'passed',
                'messages' => [
                    0 => "",
                    1 => "UnionTypes",
                    2 => '::assertTypes(string ...$types): void',
                    3 => "UnionTypes::assertTypes('NULL')",
                    4 => "it should throw InvalidUnionTypeException `NULL`, use `null` instead",
                ]
            ],
            [
                'type' => 'skipped',
                'messages' => [
                    0 => "",
                    1 => "UnionTypes",
                    2 => '::assertTypes(string ...$types): void',
                    3 => "UnionTypes::assertTypes('integer')",
                    4 => "it should throw InvalidUnionTypeException `integer`, use `int` instead",
                ],
            ],
            [
                'type' => 'pending',
                'messages' => [
                    0 => "",
                    1 => "UnionTypes",
                    2 => '::getType(mixed $value): string',
                    3 => "UnionTypes::getType(1)",
                    4 => "it should return 'int'",
                ]
            ],
            [
                'type' => 'excluded',
                'messages' => [
                    0 => "",
                    1 => "UnionTypes",
                    2 => '::getType(mixed $value): string',
                    3 => "UnionTypes::getType(1.2)",
                    4 => "it should return 'float'",
                ]
            ],
            [
                'type' => 'failed',
                'messages' => [
                    0 => "",
                    1 => "UnionTypes",
                    2 => '::getType(mixed $value): string',
                    3 => "UnionTypes::getType('1.2')",
                    4 => "it should return 'float'",
                ]
            ],
            [
                'type' => 'errored',
                'messages' => [
                    0 => "",
                    1 => "UnionTypes",
                    2 => '::getType(mixed $value): string',
                    3 => "UnionTypes::getType(new Table())",
                    4 => "it should return 'Cake\ORM\Table'",
                ]
            ]
        ];
        $it = function () use ($eraseFile, $REPORTER, $DS, $SRC, $SPEC, $specEnd, $messagesLog) {
            $file = $REPORTER . $DS . 'Console' . $DS . 'specEnd.txt';
            $eraseFile($file);

            $tree = new Tree(['colors' => false, 'output' => fopen($file, 'w'), 'src' => [$SRC], 'spec' => [$SPEC]]);
            $tree->setCount(2);
            foreach ($messagesLog as $log) {
                $tree->specEnd(new Log((string)$log['type'], (array)$log['messages']));
            }

            $expect = file_get_contents($file);
            expect($expect)->toBe($specEnd);
        };
        it("should write the `specEnd` message to the console", $it);
    });

    describe('->end($summary)', function () use ($eraseFile, $REPORTER, $DS, $SRC, $SPEC, $end, $TIME_PLACEHOLDER) {
        $messagesLog = [
            new Log(
                'failed',
                [
                    0 => "",
                    1 => "UnionTypes",
                    2 => '::assertTypes(string ...$types): void',
                    3 => "UnionTypes::assertTypes('NULL')",
                    4 => "it should throw InvalidUnionTypeException `NULL`, use `null` instead",
                ],
                './spec/UnionTypes.spec.php',
                125,
                [
                    new Expectation(
                        'failed',
                        ['actual' => 'string', 'expected' => null],
                        'toBe',
                        './spec/UnionTypes.spec.php',
                        125,
                        false,
                        'be identical to expected (===).'
                    )
                ]
            ),
            new Log(
                'errored',
                [
                    0 => "",
                    1 => "UnionTypes",
                    2 => '::assertTypes(string ...$types): void',
                    3 => "UnionTypes::assertTypes('integer')",
                    4 => "it should throw InvalidUnionTypeException `integer`, use `int` instead",
                ],
                './spec/UnionTypes.spec.php',
                131,
                [],
                new Exception(
                    'Too few arguments to function Kahlan\Matcher\ToBe::match(), 1 passed and exactly 2 expected',
                    0,
                    './vendor/kahlan/kahlan/src/Matcher/ToBe.php',
                    13,
                    [
                        [
                            "file" => "./vendor/kahlan/kahlan/src/Matcher/ToBe.php",
                            "line" => 13,
                            "function" => "match",
                            "class" => "Kahlan\Matcher\ToBe",
                            "type" => "::"
                        ],
                        [
                            "file" => "./vendor/kahlan/kahlan/src/Expectation.php",
                            "line" => 212,
                            "function" => "_spin",
                            "class" => "Kahlan\Expectation",
                            "type" => "->"
                        ],
                        [
                            "file" => "./spec/UnionTypes.spec.php",
                            "line" => 130,
                            "function" => "__call",
                            "class" => "Kahlan\Expectation",
                            "type" => "->"
                        ]
                    ]
                )
            ),
            new Log(
                'pending',
                [
                    0 => "",
                    1 => "UnionTypes",
                    2 => '::getType(mixed $value): string',
                    3 => "UnionTypes::getType(1)",
                    4 => "it should return 'int'",
                ],
                './spec/UnionTypes.spec.php',
                119
            ),
            new Log(
                'excluded',
                [
                    0 => "",
                    1 => "UnionTypes",
                    2 => '::getType(mixed $value): string',
                    3 => "UnionTypes::getType(1.2)",
                    4 => "it should return 'float'",
                ],
                './spec/UnionTypes.spec.php',
                126
            ),
            new Log(
                'skipped',
                [
                    0 => "",
                    1 => "UnionTypes",
                    2 => '::getType(mixed $value): string',
                    3 => "UnionTypes::getType('1.2')",
                    4 => "it should return 'float'",
                ],
                './spec/UnionTypes.spec.php',
                134
            ),
            new Log(
                'passed',
                [
                    0 => "",
                    1 => "UnionTypes",
                    2 => '::getType(mixed $value): string',
                    3 => "UnionTypes::getType(new Table())",
                    4 => "it should return 'Cake\ORM\Table'",
                ],
                './spec/UnionTypes.spec.php',
                145
            )
        ];
        $it = function () use ($eraseFile, $REPORTER, $DS, $SRC, $SPEC, $end, $messagesLog, $TIME_PLACEHOLDER) {
            $file = $REPORTER . $DS . 'Console' . $DS . 'end.txt';
            $eraseFile($file);

            $tree = new Tree(['colors' => false, 'output' => fopen($file, 'w'), 'src' => [$SRC], 'spec' => [$SPEC]]);
            $tree->end(new Summary($messagesLog));

            $endTxt = file_get_contents($file);
            $timeRegex = '/\d*\.\d*(?= seconds)/m';
            // `microtime(true)` inside `Terminal::_reportSummary($summary)` change from an execution to another
            // -> so we replace the generated time with a placeholder
            // e.g. from `...  in 0.023 seconds (using 2MB)` to `...  in {time} seconds (using 2MB)`
            $expect = preg_replace($timeRegex, $TIME_PLACEHOLDER, $endTxt);

            expect($expect)->toBe($end);
        };
        it("should write the `end` message to the console", $it);
    });
});
