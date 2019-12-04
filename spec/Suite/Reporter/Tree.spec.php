<?php
namespace Kahlan\Spec\Suite\Reporter\Coverage;

use Kahlan\Reporter\Tree;

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

    public function __construct($type, array $messages)
    {
        $this->_type = $type;
        $this->_messages = $messages;
    }

    public function type()
    {
        return $this->_type;
    }

    public function messages()
    {
        return $this->_messages;
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

    //region $start
    $start = <<<EOD
              _     _
    /\ /\__ _| |__ | | __ _ _ __
   / //_/ _` | '_ \| |/ _` | '_ \
  / __ \ (_| | | | | | (_| | | | |
  \/  \/\__,_|_| |_|_|\__,_|_| |_|
  
  [0;90;49mThe PHP Test Framework for Freedom, Truth and Justice.
  
[0m  [0;34;49msrc directory  : [0m$SRC
  [0;34;49mspec directory : [0m$SPEC
  
  [0;34;49mSpec Tree:[0m

EOD;
    //endregion

    //region $suiteStart
    $suiteStart = <<<EOD
  [0;90;49m│  ├── [0m::assertTypes(string ...\$types): void
  [0;90;49m│  │  ├── [0mUnionTypes::assertTypes('NULL')
  [0;90;49m│  │  ├── [0mUnionTypes::assertTypes('integer')
  [0;90;49m│  ├── [0m::getType(mixed \$value): string
  [0;90;49m│  │  ├── [0mUnionTypes::getType(1)

EOD;
    //endregion

    //region $specEnd
    $specEnd = <<<EOD
  [0;90;49m[0m[0;92;49m✓[0m   [0;90;49mit should throw InvalidUnionTypeException `NULL`, use `null` instead[0m
  [0;90;49m[0m[0;37;49m✓[0m   [0;37;49mit should throw InvalidUnionTypeException `integer`, use `int` instead[0m
  [0;90;49m[0m[0;36;49m✓[0m   [0;36;49mit should return 'int'[0m
  [0;90;49m[0m[0;33;49m✓[0m   [0;33;49mit should return 'float'[0m
  [0;90;49m[0m[0;31;49m✖[0m   [0;31;49mit should return 'float'[0m
  [0;90;49m[0m[0;31;49m✖[0m   [0;31;49mit should return 'Cake\ORM\Table'[0m

EOD;
    //endregion

    describe('->start($args)', function () use ($eraseFile, $REPORTER, $DS, $SRC, $SPEC, $start) {
        $it = function () use ($eraseFile, $REPORTER, $DS, $SRC, $SPEC, $start) {
            $file = $REPORTER . $DS . 'Console' . $DS . 'start.txt';
            $eraseFile($file);

            $tree = new Tree(['output' => fopen($file, 'w'), 'src' => [$SRC], 'spec' => [$SPEC]]);
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

            $tree = new Tree(['output' => fopen($file, 'w'), 'src' => [$SRC], 'spec' => [$SPEC]]);
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

            $tree = new Tree(['output' => fopen($file, 'w'), 'src' => [$SRC], 'spec' => [$SPEC]]);
            $tree->setCount(2);
            foreach ($messagesLog as $log) {
                $tree->specEnd(new Log((string)$log['type'], (array)$log['messages']));
            }

            $expect = file_get_contents($file);
            expect($expect)->toBe($specEnd);
        };
        it("should write the `specEnd` message to the console", $it);
    });
});
