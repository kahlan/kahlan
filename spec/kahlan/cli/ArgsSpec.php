<?php
namespace spec\cli;

use Exception;
use kahlan\cli\Args;

describe("Args", function() {

    describe("->option()", function() {

        it("sets an option config", function() {

            $args = new Args();
            $args->option('option1', ['type' => 'boolean']);
            expect($args->option('option1'))->toEqual([
                'type' => 'boolean',
                'array' => false,
                'default' => null
            ]);

        });

        it("gets the default config", function() {

            $args = new Args();
            expect($args->option('option1'))->toEqual([
                'type' => 'string',
                'array' => false,
                'default' => null
            ]);

        });

    });

    describe("->attribute()", function() {

        it("sets/updates an attribute of an option", function() {

            $args = new Args();
            $args->option('option1', []);
            $args->attribute('option1', 'default', 'value1');
            expect($args->option('option1'))->toEqual([
                'type' => 'string',
                'array' => false,
                'default' => 'value1'
            ]);

        });

    });

    describe("->parse()", function() {

        it("parses command line options", function() {

            $args = new Args();
            $actual = $args->parse([
                'command', '--option1', '--option3=value3', '--', '--ingored'
            ]);
            expect($actual)->toEqual([
                'option1' => '',
                'option3' => 'value3'
            ]);

        });

        it("parses command line options with dashed names", function() {

            $args = new Args([
                'double-dashed-option' => ['type' => 'boolean']
            ]);
            $actual = $args->parse([
                'command', '--dashed-option=value', '--double-dashed-option'
            ]);
            expect($actual)->toEqual([
                'dashed-option' => 'value',
                'double-dashed-option' => true
            ]);

        });

        it("provides an array when some multiple occurences of a same option are present", function() {

            $args = new Args(['option1' => ['array' => true]]);
            $actual = $args->parse([
                'command', '--option1', '--option1=value1' , '--option1=value2'
            ]);
            expect($actual)->toEqual([
                'option1' => [
                    '',
                    'value1',
                    'value2'
                ]
            ]);

        });

        it("allows boolean casting", function() {

            $args = new Args([
                'option1' => ['type' => 'boolean'],
                'option2' => ['type' => 'boolean'],
                'option3' => ['type' => 'boolean'],
                'option4' => ['type' => 'boolean'],
                'option5' => ['type' => 'boolean']
            ]);
            $actual = $args->parse([
                'command', '--option1', '--option2=true' , '--option3=false', '--option4=0'
            ]);
            expect($actual)->toEqual([
                'option1' => true,
                'option2' => true,
                'option3' => false,
                'option4' => false
            ]);

            expect($args->get('option5'))->toBe(false);

        });

        it("allows integer casting", function() {

            $args = new Args([
                'option'  => ['type' => 'numeric'],
                'option0' => ['type' => 'numeric'],
                'option1' => ['type' => 'numeric'],
                'option2' => ['type' => 'numeric']
            ]);
            $actual = $args->parse([
                'command', '--option', '--option0=0', '--option1=1', '--option2=2'
            ]);
            expect($actual)->toEqual([
                'option' => 1,
                'option0' => 0,
                'option1' => 1,
                'option2' => 2
            ]);

        });

        context("with defaults options", function() {

            it("allows boolean casting", function() {

                $args = new Args([
                    'option1' => ['type' => 'boolean', 'default' => true],
                    'option2' => ['type' => 'boolean', 'default' => false],
                    'option3' => ['type' => 'boolean', 'default' => true],
                    'option4' => ['type' => 'boolean', 'default' => false]
                ]);

                $actual = $args->parse([
                    'command', '--option1', '--option2'
                ]);
                expect($actual)->toEqual([
                    'option1' => true,
                    'option2' => true,
                    'option3' => true,
                    'option4' => false
                ]);

            });

        });

    });

    describe("->exists()", function() {

        it("returns `true` if the argument exists", function() {

            $args = new Args();
            $actual = $args->parse([
                'command', '--option1', '--option2=true' , '--option3=false', '--option4=0'
            ]);
            expect($args->exists('option1'))->toBe(true);
            expect($args->exists('option2'))->toBe(true);
            expect($args->exists('option3'))->toBe(true);
            expect($args->exists('option4'))->toBe(true);
            expect($args->exists('option5'))->toBe(false);

        });

        it("returns `true` if the argument as a default value", function() {

            $args = new Args();
            $args->option('option1', ['type' => 'boolean']);
            $args->option('option2', ['type' => 'boolean', 'default' => false]);

            expect($args->exists('option1'))->toBe(false);
            expect($args->exists('option2'))->toBe(true);

        });

    });

});
