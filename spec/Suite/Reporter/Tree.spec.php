<?php
namespace Kahlan\Spec\Suite\Reporter\Coverage;

use Kahlan\Dir\Dir;
use Kahlan\Reporter\Tree;
use Kahlan\Spec\Fixture\Reporter\Console\Suite;
use Kahlan\Spec\Fixture\Reporter\Console\Log;
use Kahlan\Spec\Fixture\Reporter\Console\Expectation;
use Kahlan\Spec\Fixture\Reporter\Console\Exception;
use Kahlan\Spec\Fixture\Reporter\Console\Summary;

describe("Tree", function () {

    beforeAll(function () {
        $this->srcDir = realpath('src');
        $this->specDir = realpath('spec');
        $this->timePlaceholder = '{time}';
    });

    beforeEach(function () {
        $this->file = fopen('php://memory', 'rw');
    });

    afterEach(function () {
        if (is_resource($this->file)) {
            fclose($this->file);
        }
    });

    describe('->start($args)', function () {
        it("should write the `start` message to the console", function () {
            $tree = new Tree(['colors' => false, 'output' => $this->file, 'src' => [$this->srcDir], 'spec' => [$this->specDir]]);
            $tree->start(['total' => 0]);

            fseek($this->file, 0);
            $expected = stream_get_contents($this->file);
            expect($expected)->toBe(sprintf(file_get_contents('spec/Fixture/Reporter/Console/start.txt'), $this->srcDir, $this->specDir));
        });
    });

    describe('->suiteStart($suite = null)', function () {
        it('should return if `$suite === null`', function () {
            $tree = new Tree();
            $expect = $tree->suiteStart(null);
            expect($expect)->toBeNull();
        });

        it("should write the `suiteStart` message to the console", function () {
            $messagesSuite = [
                [
                    ''
                ],
                [
                    '',
                    'UnionTypes',
                    '::assertTypes(string ...$types): void',
                ],
                [
                    '',
                    'UnionTypes',
                    '::assertTypes(string ...$types): void',
                    'UnionTypes::assertTypes(\'NULL\')',
                ],
                [
                    '',
                    'UnionTypes',
                    '::assertTypes(string ...$types): void',
                    'UnionTypes::assertTypes(\'integer\')',
                ],
                [
                    '',
                    'UnionTypes',
                    '::getType(mixed $value): string',
                ],
                [
                    '',
                    'UnionTypes',
                    '::getType(mixed $value): string',
                    'UnionTypes::getType(1)',
                ]
            ];

            $tree = new Tree(['colors' => false, 'output' => $this->file, 'src' => [$this->srcDir], 'spec' => [$this->specDir]]);
            foreach ($messagesSuite as $messages) {
                $tree->suiteStart(new Suite($messages));
            }

            fseek($this->file, 0);
            $expected = stream_get_contents($this->file);
            expect($expected)->toBe(sprintf(file_get_contents('spec/Fixture/Reporter/Console/suiteStart.txt'), $this->srcDir, $this->specDir));
        });
    });

    describe('->specEnd($log = null)', function () {
        it('should return if `$log === null`', function () {
            $tree = new Tree();
            $expect = $tree->specEnd(null);
            expect($expect)->toBeNull();
        });

        it("should write the `specEnd` message to the console", function () {

            $messagesLog = [
                [
                    'type' => 'passed',
                    'messages' => [
                        '',
                        'UnionTypes',
                        '::assertTypes(string ...$types): void',
                        'UnionTypes::assertTypes(\'NULL\')',
                        'it should throw InvalidUnionTypeException `NULL`, use `null` instead',
                    ]
                ],
                [
                    'type' => 'skipped',
                    'messages' => [
                        '',
                        'UnionTypes',
                        '::assertTypes(string ...$types): void',
                        'UnionTypes::assertTypes(\'integer\')',
                        'it should throw InvalidUnionTypeException `integer`, use `int` instead',
                    ],
                ],
                [
                    'type' => 'pending',
                    'messages' => [
                        '',
                        'UnionTypes',
                        '::getType(mixed $value): string',
                        'UnionTypes::getType(1)',
                        'it should return \'int\'',
                    ]
                ],
                [
                    'type' => 'excluded',
                    'messages' => [
                        '',
                        'UnionTypes',
                        '::getType(mixed $value): string',
                        'UnionTypes::getType(1.2)',
                        'it should return \'float\'',
                    ]
                ],
                [
                    'type' => 'failed',
                    'messages' => [
                        '',
                        'UnionTypes',
                        '::getType(mixed $value): string',
                        'UnionTypes::getType(\'1.2\')',
                        'it should return \'float\'',
                    ]
                ],
                [
                    'type' => 'errored',
                    'messages' => [
                        '',
                        'UnionTypes',
                        '::getType(mixed $value): string',
                        'UnionTypes::getType(new Table())',
                        'it should return \'Cake\ORM\Table\'',
                    ]
                ]
            ];

            $tree = new Tree(['colors' => false, 'output' => $this->file, 'src' => [$this->srcDir], 'spec' => [$this->specDir]]);
            $tree->setCount(2);
            foreach ($messagesLog as $log) {
                $tree->specEnd(new Log((string)$log['type'], (array)$log['messages']));
            }

            fseek($this->file, 0);
            $expected = stream_get_contents($this->file);
            expect($expected)->toBe(file_get_contents('spec/Fixture/Reporter/Console/specEnd.txt'));

        });
    });

    describe('->end($summary)', function () {

        it("should write the `end` message to the console", function () {

            $messagesLog = [
                new Log(
                    'failed',
                    [
                        '',
                        'UnionTypes',
                        '::assertTypes(string ...$types): void',
                        'UnionTypes::assertTypes(\'NULL\')',
                        'it should throw InvalidUnionTypeException `NULL`, use `null` instead',
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
                        '',
                        'UnionTypes',
                        '::assertTypes(string ...$types): void',
                        'UnionTypes::assertTypes(\'integer\')',
                        'it should throw InvalidUnionTypeException `integer`, use `int` instead',
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
                                'file' => './vendor/kahlan/kahlan/src/Matcher/ToBe.php',
                                'line' => 13,
                                'function' => 'match',
                                'class' => 'Kahlan\Matcher\ToBe',
                                'type' => '::'
                            ],
                            [
                                'file' => './vendor/kahlan/kahlan/src/Expectation.php',
                                'line' => 212,
                                'function' => '_spin',
                                'class' => 'Kahlan\Expectation',
                                'type' => '->'
                            ],
                            [
                                'file' => './spec/UnionTypes.spec.php',
                                'line' => 130,
                                'function' => '__call',
                                'class' => 'Kahlan\Expectation',
                                'type' => '->'
                            ]
                        ]
                    )
                ),
                new Log(
                    'pending',
                    [
                        '',
                        'UnionTypes',
                        '::getType(mixed $value): string',
                        'UnionTypes::getType(1)',
                        'it should return \'int\'',
                    ],
                    './spec/UnionTypes.spec.php',
                    119
                ),
                new Log(
                    'excluded',
                    [
                        '',
                        'UnionTypes',
                        '::getType(mixed $value): string',
                        'UnionTypes::getType(1.2)',
                        'it should return \'float\'',
                    ],
                    './spec/UnionTypes.spec.php',
                    126
                ),
                new Log(
                    'skipped',
                    [
                        '',
                        'UnionTypes',
                        '::getType(mixed $value): string',
                        'UnionTypes::getType(\'1.2\')',
                        'it should return \'float\'',
                    ],
                    './spec/UnionTypes.spec.php',
                    134
                ),
                new Log(
                    'passed',
                    [
                        '',
                        'UnionTypes',
                        '::getType(mixed $value): string',
                        'UnionTypes::getType(new Table())',
                        'it should return \'Cake\ORM\Table\'',
                    ],
                    './spec/UnionTypes.spec.php',
                    145
                )
            ];

            $tree = new Tree(['colors' => false, 'output' => $this->file, 'src' => [$this->srcDir], 'spec' => [$this->specDir]]);
            $tree->end(new Summary($messagesLog));

            fseek($this->file, 0);
            $endTxt = stream_get_contents($this->file);
            $timeRegex = '/\d*\.\d*(?= seconds)/m';
            // `microtime(true)` inside `Terminal::_reportSummary($summary)` change from an execution to another
            // -> so we replace the generated time with a placeholder
            // e.g. from `...  in 0.023 seconds (using 2MB)` to `...  in {time} seconds (using 2MB)`
            $expected = preg_replace($timeRegex, $this->timePlaceholder, $endTxt);

            expect($expected)->toBe(file_get_contents('spec/Fixture/Reporter/Console/end.txt'));
        });
    });
});
