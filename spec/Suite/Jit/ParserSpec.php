<?php
namespace Kahlan\Spec\Jit\Suite;

use Kahlan\Jit\Parser;

describe("Parser", function() {

    beforeEach(function() {
        $this->flattenTree = function($nodes, $self) {
            $result = [] ;
            foreach ($nodes as $node) {
                if (count($node->tree)) {
                    $result = array_merge($result, $self->flattenTree($node->tree, $self));
                } else {
                    $result[] = $node;
                }
            }
            return $result;
        };
    });

    describe("->parse()", function() {

        it("parses consistently", function() {
            $sample = file_get_contents('spec/Fixture/Jit/Parser/Sample.php');
            $parsed = Parser::parse($sample);
            expect(Parser::unparse($parsed))->toBe($sample);

        });

        it("parses syntaxically broken use statement and doesn't crash", function() {

            $code = "<?php use MyClass?>";
            $parsed = Parser::parse($code);
            expect(Parser::unparse($parsed))->toBe($code);

        });

        it("parses functions", function() {

            $sample = file_get_contents('spec/Fixture/Jit/Parser/Function.php');
            $root = Parser::parse($sample);
            foreach ($root->tree as $node) {
                if ($node->type === 'function') {
                    expect($node->name)->toBe('myFunction');
                    expect($node->isClosure)->toBe(false);
                    expect($node->isMethod)->toBe(false);
                    expect($node->isGenerator)->toBe(false);
                    expect($node->parent)->toBe($root);
                    expect($node->args)->toBe([
                        '$required',
                        '$param'    => '"value"',
                        '$boolean'  => 'false',
                        '$array'    => '[]',
                        '$array2'   => 'array()',
                        '$constant' => 'PI'
                    ]);
                }
            }

        });

        it("parses PHP directly when the `'php'` option is set to true", function() {

            $code = "namespace MyNamespace;";
            $root = Parser::parse($code, ['php' => true]);
            $nodes = $this->flattenTree($root->tree, $this);
            $namespace = current($nodes);
            expect($namespace->type)->toBe('namespace');
            expect(Parser::unparse($root))->toBe($code);

        });

        it("correctly populates the `->inPhp` attribute", function() {

            $sample = file_get_contents('spec/Fixture/Jit/Parser/Sample.php');
            $root = Parser::parse($sample);
            $plain = [];

            foreach ($this->flattenTree($root->tree, $this) as $node) {
                if(!$node->inPhp) {
                    $plain[] = (string) $node;
                }
            }

            expect($plain)->toBe([
                "<?php\n",
                "?>\n",
                "\n<i> Hello World </i>\n\n",
                "<?php\n",
                "?>\n",
                "<?php\n"
            ]);
        });

        it("correctly populates the `->isGenerator` attribute", function() {

            skipIf(version_compare(phpversion(), '5.5', '<'));

            $sample = file_get_contents('spec/Fixture/Jit/Parser/Generator.php');
            $root = Parser::parse($sample);
            foreach ($root->tree as $node) {
                if ($node->type === 'function') {
                    expect($node->name)->toBe('myGenerator');
                    expect($node->isClosure)->toBe(false);
                    expect($node->isMethod)->toBe(false);
                    expect($node->isGenerator)->toBe(true);
                    expect($node->parent)->toBe($root);
                }
            }

        });

    });

    describe("->debug()", function() {

        it("attaches the correct lines", function() {

            $filename = 'spec/Fixture/Jit/Parser/Sample';
            $content = file_get_contents($filename . '.php');

            $parsed = Parser::debug($content);
            expect($parsed)->toBe(file_get_contents($filename . '.txt'));

            $parsed = Parser::parse($content);
            expect(Parser::unparse($parsed))->toBe($content);

        });

        it("parses files with no namespace", function() {

            $filename = 'spec/Fixture/Jit/Parser/NoNamespace';
            $content = file_get_contents($filename . '.php');

            $parsed = Parser::debug($content);
            expect($parsed)->toBe(file_get_contents($filename . '.txt'));

            $parsed = Parser::parse($content);
            expect(Parser::unparse($parsed))->toBe($content);

        });

        it("parses heredoc", function() {

            $filename = 'spec/Fixture/Jit/Parser/Heredoc';
            $content = file_get_contents($filename . '.php');

            $parsed = Parser::debug($content);
            expect($parsed)->toBe(file_get_contents($filename . '.txt'));

            $parsed = Parser::parse($content);
            expect(Parser::unparse($parsed))->toBe($content);

        });

        it("parses strings", function() {

            $filename = 'spec/Fixture/Jit/Parser/String';
            $content = file_get_contents($filename . '.php');

            $parsed = Parser::debug($content);
            expect($parsed)->toBe(file_get_contents($filename . '.txt'));

            $parsed = Parser::parse($content);
            expect(Parser::unparse($parsed))->toBe($content);

        });

        it("parses char at syntax", function() {

            $filename = 'spec/Fixture/Jit/Parser/CharAtSyntax';
            $content = file_get_contents($filename . '.php');

            $parsed = Parser::debug($content);
            expect($parsed)->toBe(file_get_contents($filename . '.txt'));

            $parsed = Parser::parse($content);
            expect(Parser::unparse($parsed))->toBe($content);

        });

        it("parses closures", function() {

            $filename = 'spec/Fixture/Jit/Parser/Closure';
            $content = file_get_contents($filename . '.php');

            $parsed = Parser::debug($content);
            expect($parsed)->toBe(file_get_contents($filename . '.txt'));

            $parsed = Parser::parse($content);
            expect(Parser::unparse($parsed))->toBe($content);

        });

        it("parses switch cases", function() {

            $filename = 'spec/Fixture/Jit/Parser/Switch';
            $content = file_get_contents($filename . '.php');

            $parsed = Parser::debug($content);
            expect($parsed)->toBe(file_get_contents($filename . '.txt'));

            $parsed = Parser::parse($content);
            expect(Parser::unparse($parsed))->toBe($content);

        });

        it("parses uses", function() {

            $filename = 'spec/Fixture/Jit/Parser/Uses';
            $content = file_get_contents($filename . '.php');

            $parsed = Parser::parse($content);
            expect($parsed->uses)->toBe([
                'A' => 'Kahlan\A',
                'B' => 'Kahlan\B',
                'C' => 'Kahlan\C',
                'F' => 'Kahlan\E',
                'StandardClass' => 'stdClass'
            ]);

            $parsed = Parser::parse($content);
            expect(Parser::unparse($parsed))->toBe($content);

        });

        it("parses ::class syntax", function() {

            $filename = 'spec/Fixture/Jit/Parser/StaticClassKeyword';
            $content = file_get_contents($filename . '.php');

            $parsed = Parser::debug($content);
            expect($parsed)->toBe(file_get_contents($filename . '.txt'));

            $parsed = Parser::parse($content);
            expect(Parser::unparse($parsed))->toBe($content);

        });

    });

});
