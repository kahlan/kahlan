<?php
namespace kahlan\spec\suite\cli;

use kahlan\cli\Args;

describe("Args", function() {

    describe("->argument()", function() {

        it("sets an argument config", function() {

            $args = new Args();
            $args->argument('argument1', ['type' => 'boolean']);
            expect($args->argument('argument1'))->toEqual([
                'type'    => 'boolean',
                'array'   => false,
                'value'   => null,
                'default' => null
            ]);

            $arguments = $args->arguments();
            expect($arguments)->toBeAn('array');
            expect(isset($arguments['argument1']))->toBe(true);
            expect($arguments['argument1'])->toEqual([
                'type'    => 'boolean',
                'array'   => false,
                'value'   => null,
                'default' => null
            ]);

        });

        it("gets the default config", function() {

            $args = new Args();
            expect($args->argument('argument1'))->toEqual([
                'type'    => 'string',
                'array'   => false,
                'value'   => null,
                'default' => null
            ]);

        });

        it("sets/updates an attribute of an argument", function() {

            $args = new Args();
            $args->argument('argument1', ['type' => 'boolean']);
            expect($args->argument('argument1'))->toEqual([
                'type'    => 'boolean',
                'array'   => false,
                'value'   => null,
                'default' => null
            ]);

            $args->argument('argument1', 'default', 'value1');
            expect($args->argument('argument1'))->toEqual([
                'type'    => 'boolean',
                'array'   => false,
                'value'   => null,
                'default' => 'value1'
            ]);

        });

    });

    describe("->parse()", function() {

        it("parses command line arguments", function() {

            $args = new Args();
            $actual = $args->parse([
                'command', '--argument1', '--argument3=value3', '--', '--ingored'
            ]);
            expect($actual)->toEqual([
                'argument1' => '',
                'argument3' => 'value3'
            ]);

        });

        it("parses command line arguments with dashed names", function() {

            $args = new Args([
                'double-dashed-argument' => ['type' => 'boolean']
            ]);
            $actual = $args->parse([
                'command', '--dashed-argument=value', '--double-dashed-argument'
            ]);
            expect($actual)->toEqual([
                'dashed-argument' => 'value',
                'double-dashed-argument' => true
            ]);

        });

        it("provides an array when some multiple occurences of a same argument are present", function() {

            $args = new Args(['argument1' => ['array' => true]]);
            $actual = $args->parse([
                'command', '--argument1', '--argument1=value1' , '--argument1=value2'
            ]);
            expect($actual)->toEqual([
                'argument1' => [
                    '',
                    'value1',
                    'value2'
                ]
            ]);

        });

        it("casts booleans", function() {

            $args = new Args([
                'argument1' => ['type' => 'boolean'],
                'argument2' => ['type' => 'boolean'],
                'argument3' => ['type' => 'boolean'],
                'argument4' => ['type' => 'boolean'],
                'argument5' => ['type' => 'boolean']
            ]);
            $actual = $args->parse([
                'command', '--argument1', '--argument2=true' , '--argument3=false', '--argument4=0'
            ]);
            expect($actual)->toEqual([
                'argument1' => true,
                'argument2' => true,
                'argument3' => false,
                'argument4' => false
            ]);

            expect($args->get('argument5'))->toBe(false);

        });

        it("casts integers", function() {

            $args = new Args([
                'argument'  => ['type' => 'numeric'],
                'argument0' => ['type' => 'numeric'],
                'argument1' => ['type' => 'numeric'],
                'argument2' => ['type' => 'numeric']
            ]);
            $actual = $args->parse([
                'command', '--argument', '--argument0=0', '--argument1=1', '--argument2=2'
            ]);
            expect($actual)->toEqual([
                'argument' => 1,
                'argument0' => 0,
                'argument1' => 1,
                'argument2' => 2
            ]);

        });

        it("casts string", function() {

            $args = new Args([
                'argument1' => ['type' => 'string'],
                'argument2' => ['type' => 'string'],
                'argument3' => ['type' => 'string'],
                'argument4' => ['type' => 'string'],
                'argument5' => ['type' => 'string']
            ]);
            $actual = $args->parse([
                'command', '--argument1', '--argument2=' , '--argument3=value'
            ]);
            expect($actual)->toEqual([
                'argument1' => null,
                'argument2' => '',
                'argument3' => 'value'
            ]);

            expect($args->get('argument5'))->toBe(null);

        });

        context("with defaults arguments", function() {

            it("allows boolean casting", function() {

                $args = new Args([
                    'argument1' => ['type' => 'boolean', 'default' => true],
                    'argument2' => ['type' => 'boolean', 'default' => false],
                    'argument3' => ['type' => 'boolean', 'default' => true],
                    'argument4' => ['type' => 'boolean', 'default' => false]
                ]);

                $actual = $args->parse([
                    'command', '--argument1', '--argument2'
                ]);
                expect($actual)->toEqual([
                    'argument1' => true,
                    'argument2' => true,
                    'argument3' => true,
                    'argument4' => false
                ]);

            });

        });

        context("with override set to `false`", function() {

            it("doesn't override existing arguments when the override params is set to `false`", function() {

                $args = new Args();
                $args->set('argument1', 'value1');
                $actual = $args->parse(['--argument1=valueX']);
                expect($actual)->toBe(['argument1' => 'valueX']);

                $args = new Args();
                $args->set('argument1', 'value1');
                $actual = $args->parse(['--argument1=valueX'], false);
                expect($actual)->toBe(['argument1' => 'value1']);

            });

        });

    });

    describe("->get()", function() {

        it("ignores argument value if the value option is set", function() {

            $args = new Args(['argument1' => [
                'type'    => 'string',
                'value'   => 'config_value'
            ]]);

            $actual = $args->parse(['command']);
            expect($args->get('argument1'))->toEqual('config_value');

            $actual = $args->parse(['command', '--argument1']);
            expect($args->get('argument1'))->toEqual('config_value');

            $actual = $args->parse(['command', '--argument1="some_value"']);
            expect($args->get('argument1'))->toEqual('config_value');

        });

        it("formats value according to value function", function() {

            $args = new Args(['argument1' => [
                'type'    => 'string',
                'default' => 'default_value',
                'value'   => function($value, $name, $args) {
                    if (!$value) {
                        return  'empty_value';
                    }
                    return 'non_empty_value';
                }
            ]]);

            $actual = $args->parse(['command']);
            expect($args->get('argument1'))->toEqual('default_value');

            $actual = $args->parse(['command', '--argument1']);
            expect($args->get('argument1'))->toEqual('empty_value');

            $actual = $args->parse(['command', '--argument1="some_value"']);
            expect($args->get('argument1'))->toEqual('non_empty_value');

        });

    });

    describe("->exists()", function() {

        it("returns `true` if the argument exists", function() {

            $args = new Args();
            $actual = $args->parse([
                'command', '--argument1', '--argument2=true' , '--argument3=false', '--argument4=0'
            ]);
            expect($args->exists('argument1'))->toBe(true);
            expect($args->exists('argument2'))->toBe(true);
            expect($args->exists('argument3'))->toBe(true);
            expect($args->exists('argument4'))->toBe(true);
            expect($args->exists('argument5'))->toBe(false);

        });

        it("returns `true` if the argument as a default value", function() {

            $args = new Args();
            $args->argument('argument1', ['type' => 'boolean']);
            $args->argument('argument2', ['type' => 'boolean', 'default' => false]);

            expect($args->exists('argument1'))->toBe(false);
            expect($args->exists('argument2'))->toBe(true);

        });

    });

    describe("->cast()", function() {

        it("casts array", function() {

            $args = new Args();
            $cast = $args->cast(["some", "string", "and", 10], "string");
            expect($cast)->toBeAn('array');
            foreach($cast as $c) {
                expect($c)->toBeA('string');
            }

        });

        it("casts boolean", function() {

            $args = new Args();
            $cast = $args->cast(["true", "false", "some_string", null, 10], "boolean");
            expect($cast)->toBeAn('array');
            expect(count($cast))->toBe(5);
            list($bTrue, $bFalse, $string, $null, $number) = $cast;
            expect($bTrue)->toBeA('boolean')->toBe(true);
            expect($bFalse)->toBeA('boolean')->toBe(false);
            expect($string)->toBeA('boolean')->toBe(true);
            expect($null)->toBeA('boolean')->toBe(false);
            expect($number)->toBeA('boolean')->toBe(true);

        });

        it("casts numeric", function() {

            $args = new Args();
            $cast = $args->cast([true, "false", "some_string", null, 10], "numeric");
            expect($cast)->toBeAn('array');
            expect(count($cast))->toBe(5);
            expect(implode($cast))->toBe("100110");

        });

        it("casts value into array", function() {

            $args = new Args();
            $cast = $args->cast("string", "string", true);
            expect($cast)->toBeA("array");
            expect($cast)->toContain("string");

        });

    });

});
