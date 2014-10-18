<?php
namespace spec\cli;

use kahlan\cli\Args;

describe("Args", function() {

    describe("parse", function() {

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

            expect($args->get('option5'))->toEqual(false);
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

    });

});

?>