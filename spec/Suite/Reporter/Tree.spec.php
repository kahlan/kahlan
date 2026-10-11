<?php
namespace Kahlan\Spec\Suite\Reporter\Coverage;

use Kahlan\Dir\Dir;
use Kahlan\Matcher;
use Kahlan\Reporters;
use Kahlan\Suite as KahlanSuite;
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

            skipIfWindows();

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

            skipIfWindows();

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

    describe('->suiteEnd($suite = null)', function () {
        it('should return if `$suite === null`', function () {
            $tree = new Tree();
            $expect = $tree->suiteEnd(null);
            expect($expect)->toBeNull();
        });

        it("should restore the indentation so specs following a nested suite are aligned with their siblings", function () {

            $tree = new Tree(['colors' => false, 'output' => $this->file, 'src' => [$this->srcDir], 'spec' => [$this->specDir]]);

            $tree->suiteStart(new Suite(['']));
            $tree->suiteStart(new Suite(['', 'A']));
            $tree->specEnd(new Log('passed', ['', 'A', 'it A1']));
            $tree->suiteStart(new Suite(['', 'A', 'B']));
            $tree->specEnd(new Log('passed', ['', 'A', 'B', 'it B1']));
            $tree->specEnd(new Log('passed', ['', 'A', 'B', 'it B2']));
            $tree->suiteEnd(new Suite(['', 'A', 'B']));
            $tree->specEnd(new Log('passed', ['', 'A', 'it A2']));

            fseek($this->file, 0);
            $actual = stream_get_contents($this->file);

            $expected = implode("\n", [
                '├── A',
                '✓   it A1',
                '│  ├── B',
                '│  ✓   it B1',
                '│  ✓   it B2',
                '✓   it A2',
                ''
            ]);
            expect($actual)->toBe($expected);
        });

        it("should restore the indentation to the right ancestor level on deeply nested suites, including when several suites end at once", function () {

            $tree = new Tree(['colors' => false, 'output' => $this->file, 'src' => [$this->srcDir], 'spec' => [$this->specDir]]);

            $tree->suiteStart(new Suite(['']));

            $tree->suiteStart(new Suite(['', 'A']));
            $tree->specEnd(new Log('passed', ['', 'A', 'it A1']));
            $tree->suiteStart(new Suite(['', 'A', 'B']));
            $tree->suiteStart(new Suite(['', 'A', 'B', 'C']));
            $tree->suiteStart(new Suite(['', 'A', 'B', 'C', 'D']));
            $tree->specEnd(new Log('passed', ['', 'A', 'B', 'C', 'D', 'it D1']));
            $tree->suiteEnd(new Suite(['', 'A', 'B', 'C', 'D']));
            $tree->specEnd(new Log('passed', ['', 'A', 'B', 'C', 'it C1']));
            $tree->suiteEnd(new Suite(['', 'A', 'B', 'C']));
            $tree->specEnd(new Log('passed', ['', 'A', 'B', 'it B1']));
            $tree->suiteEnd(new Suite(['', 'A', 'B']));
            $tree->specEnd(new Log('passed', ['', 'A', 'it A2']));
            $tree->suiteEnd(new Suite(['', 'A']));

            $tree->suiteStart(new Suite(['', 'X']));
            $tree->suiteStart(new Suite(['', 'X', 'Y']));
            $tree->suiteStart(new Suite(['', 'X', 'Y', 'Z']));
            $tree->specEnd(new Log('passed', ['', 'X', 'Y', 'Z', 'it Z1']));
            $tree->suiteEnd(new Suite(['', 'X', 'Y', 'Z']));
            $tree->suiteEnd(new Suite(['', 'X', 'Y']));
            $tree->specEnd(new Log('passed', ['', 'X', 'it X1']));
            $tree->suiteEnd(new Suite(['', 'X']));

            fseek($this->file, 0);
            $actual = stream_get_contents($this->file);

            $expected = implode("\n", [
                '├── A',
                '✓   it A1',
                '│  ├── B',
                '│  │  ├── C',
                '│  │  │  ├── D',
                '│  │  │  ✓   it D1',
                '│  │  ✓   it C1',
                '│  ✓   it B1',
                '✓   it A2',
                '├── X',
                '│  ├── Y',
                '│  │  ├── Z',
                '│  │  ✓   it Z1',
                '✓   it X1',
                ''
            ]);
            expect($actual)->toBe($expected);
        });
    });

    describe('->specEnd($log = null)', function () {
        it('should return if `$log === null`', function () {
            $tree = new Tree();
            $expect = $tree->specEnd(null);
            expect($expect)->toBeNull();
        });

        it("should not indent a spec logged above the first suite level", function () {

            $tree = new Tree(['colors' => false, 'output' => $this->file]);
            $tree->setCount(1);
            $tree->specEnd(new Log('passed', ['', 'it passes']));

            fseek($this->file, 0);
            expect(stream_get_contents($this->file))->toBe("✓   it passes\n");

        });

        it("should write the `specEnd` message to the console", function () {

            skipIfWindows();

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

        it("should report a failure of a top-level suite", function () {

            $tree = new Tree(['colors' => false, 'output' => $this->file]);
            $tree->end(new Summary([new Log('failed', ['', 'UnionTypes'])]));

            fseek($this->file, 0);
            expect(stream_get_contents($this->file))->toContain("Failure Tree(1):\n✖   UnionTypes\n");

        });

        it("should report a failure of the root suite", function () {

            $tree = new Tree(['colors' => false, 'output' => $this->file]);
            $tree->end(new Summary([new Log('failed', [''])]));

            fseek($this->file, 0);
            expect(stream_get_contents($this->file))->toContain("Failure Tree(1):\n✖   \n");

        });

        it("should write the `end` message to the console", function () {

            skipIfWindows();

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

    describe("when running a suite", function () {

        beforeEach(function () {
            $this->suite = new KahlanSuite(['matcher' => new Matcher()]);
            $this->output = fopen('php://memory', 'rw');
            $this->reporters = new Reporters();
            $this->reporters->add('tree', new Tree(['colors' => false, 'output' => $this->output]));
        });

        it("reports a failing `beforeAll()` of a top-level suite", function () {

            $this->suite->root()->describe("A", function () {
                $this->beforeAll(function () {
                    throw new \Exception('Oops');
                });
                $this->it("passes", function () {
                    $this->expect(true)->toBe(true);
                });
            });

            $this->suite->run(['reporters' => $this->reporters]);

            fseek($this->output, 0);
            $output = stream_get_contents($this->output);
            expect($output)->toContain("Failure Tree(1):\n✖   A\n");
            expect($output)->toContain('with message "Oops"');

        });

        it("reports a failing `beforeAll()` of the root suite", function () {

            $this->suite->root()->beforeAll(function () {
                throw new \Exception('Oops');
            });
            $this->suite->root()->describe("A", function () {
                $this->it("passes", function () {
                    $this->expect(true)->toBe(true);
                });
            });

            $this->suite->run(['reporters' => $this->reporters]);

            fseek($this->output, 0);
            $output = stream_get_contents($this->output);
            expect($output)->toContain("Failure Tree(1):\n✖   \n");
            expect($output)->toContain('with message "Oops"');

        });

    });
});
