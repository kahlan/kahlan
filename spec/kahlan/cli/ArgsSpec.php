<?php
namespace spec\kahlan\cli;

use Exception;
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

        it("allows boolean casting", function() {

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

        it("allows integer casting", function() {

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

});
