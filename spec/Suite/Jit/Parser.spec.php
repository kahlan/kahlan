<?php
namespace Kahlan\Spec\Jit\Suite;

use Kahlan\Jit\Parser;

describe("Parser", function () {

    beforeEach(function () {
        $this->flattenTree = function ($nodes, $self) {
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

    describe("->parse()", function () {

        it("parses consistently", function () {

            $sample = file_get_contents('spec/Fixture/Jit/Parser/Sample.php');
            $parsed = Parser::parse($sample);
            expect(Parser::unparse($parsed))->toBe($sample);

        });

        it("parses syntaxically broken use statement and doesn't crash", function () {

            $code = "<?php use MyClass?>";
            $parsed = Parser::parse($code);
            expect(Parser::unparse($parsed))->toBe($code);

        });

        it("parses functions", function () {

            $sample = file_get_contents('spec/Fixture/Jit/Parser/Function.php');
            $root = Parser::parse($sample);
            foreach ($root->tree as $node) {
                if ($node->type === 'function') {
                    expect($node->name)->toBe('myFunction');
                    expect($node->isClosure)->toBeFalsy();
                    expect($node->isMethod)->toBeFalsy();
                    expect($node->isGenerator)->toBeFalsy();
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

        it("parses arrow functions", function () {

            skipIf(PHP_VERSION_ID < 70400);

            $sample = file_get_contents('spec/Fixture/Jit/Parser/ArrowFunction.php');
            $root = Parser::parse($sample);
            foreach ($root->tree as $node) {
                if ($node->type === 'function') {
                    expect($node->name)->toBe('');
                    expect($node->isClosure)->toBeTruthy();
                    expect($node->isMethod)->toBeFalsy();
                    expect($node->isGenerator)->toBeFalsy();
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

        it("parses arrow functions", function () {

            skipIf(PHP_VERSION_ID < 70400);

            $filename = 'spec/Fixture/Jit/Parser/ArrowFunction';
            $content = file_get_contents($filename . '.php');

            $parsed = Parser::debug($content);
            expect($parsed)->toBe(file_get_contents($filename . '.txt'));

            $parsed = Parser::parse($content);

            foreach ($parsed->tree as $node) {
                if ($node->type === 'function') {
                    expect($node->name)->toBe('');
                    expect($node->isClosure)->toBe(true);
                    expect($node->isMethod)->toBe(false);
                    expect($node->isGenerator)->toBe(false);
                    expect($node->parent)->toBe($parsed);
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

            expect(Parser::unparse($parsed))->toBe($content);
        });

        it("parses PHP directly when the `'php'` option is set to true", function () {

            $code = "namespace MyNamespace;";
            $root = Parser::parse($code, ['php' => true]);
            $nodes = $this->flattenTree($root->tree, $this);
            $namespace = current($nodes);
            expect($namespace->type)->toBe('namespace');
            expect(Parser::unparse($root))->toBe($code);

        });

        it("correctly populates the `->inPhp` attribute", function () {

            $sample = file_get_contents('spec/Fixture/Jit/Parser/Sample.php');
            $root = Parser::parse($sample);
            $plain = [];

            foreach ($this->flattenTree($root->tree, $this) as $node) {
                if (!$node->inPhp) {
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

        it("rebases __DIR__ and __FILE__ magic constants", function () {

            $nodes = Parser::parse(file_get_contents('spec/Fixture/Jit/Parser/Rebase.php'), ['path' => '/the/original/path/Rebase.php']);
            $expected = file_get_contents('spec/Fixture/Jit/Parser/RebaseProcessed.php');
            $actual = Parser::unparse($nodes);
            expect($actual)->toBe($expected);

        });

    });

    describe("->debug()", function () {

        it("attaches the correct lines", function () {

            $filename = 'spec/Fixture/Jit/Parser/Sample';
            $content = file_get_contents($filename . '.php');

            $parsed = Parser::debug($content);
            expect($parsed)->toBe(file_get_contents($filename . '.txt'));

            $parsed = Parser::parse($content);
            expect(Parser::unparse($parsed))->toBe($content);

        });

        it("parses files with no namespace", function () {

            $filename = 'spec/Fixture/Jit/Parser/NoNamespace';
            $content = file_get_contents($filename . '.php');

            $parsed = Parser::debug($content);
            expect($parsed)->toBe(file_get_contents($filename . '.txt'));

            $parsed = Parser::parse($content);
            expect(Parser::unparse($parsed))->toBe($content);

        });

        it("parses heredoc", function () {

            $filename = 'spec/Fixture/Jit/Parser/Heredoc';
            $content = file_get_contents($filename . '.php');

            $parsed = Parser::debug($content);
            expect($parsed)->toBe(file_get_contents($filename . '.txt'));

            $parsed = Parser::parse($content);
            expect(Parser::unparse($parsed))->toBe($content);

        });

        it("parses nowdoc", function () {

            $filename = 'spec/Fixture/Jit/Parser/Nowdoc';
            $content = file_get_contents($filename . '.php');

            $parsed = Parser::debug($content);
            expect($parsed)->toBe(file_get_contents($filename . '.txt'));

            $parsed = Parser::parse($content);
            expect(Parser::unparse($parsed))->toBe($content);

        });

        it("parses strings", function () {

            $filename = 'spec/Fixture/Jit/Parser/String';
            $content = file_get_contents($filename . '.php');

            $parsed = Parser::debug($content);
            expect($parsed)->toBe(file_get_contents($filename . '.txt'));

            $parsed = Parser::parse($content);
            expect(Parser::unparse($parsed))->toBe($content);

        });

        it("parses char at syntax", function () {

            $filename = 'spec/Fixture/Jit/Parser/CharAtSyntax';
            $content = file_get_contents($filename . '.php');

            $parsed = Parser::debug($content);
            expect($parsed)->toBe(file_get_contents($filename . '.txt'));

            $parsed = Parser::parse($content);
            expect(Parser::unparse($parsed))->toBe($content);

        });

        it("parses closures", function () {

            $filename = 'spec/Fixture/Jit/Parser/Closure';
            $content = file_get_contents($filename . '.php');

            $parsed = Parser::debug($content);
            expect($parsed)->toBe(file_get_contents($filename . '.txt'));

            $parsed = Parser::parse($content);
            expect(Parser::unparse($parsed))->toBe($content);

        });

        it("parses switch cases", function () {

            $filename = 'spec/Fixture/Jit/Parser/Switch';
            $content = file_get_contents($filename . '.php');

            $parsed = Parser::debug($content);
            expect($parsed)->toBe(file_get_contents($filename . '.txt'));

            $parsed = Parser::parse($content);
            expect(Parser::unparse($parsed))->toBe($content);

        });

        it("parses uses", function () {

            $filename = 'spec/Fixture/Jit/Parser/Uses';
            $content = file_get_contents($filename . '.php');

            $parsed = Parser::parse($content);
            expect($parsed->uses)->toBe([
                'A' => 'Kahlan\A',
                'B' => 'Kahlan\B',
                'C' => 'Kahlan\C',
                'F' => 'Kahlan\E',
                'G' => 'Kahlan\E',
                'StandardClass' => 'stdClass',
                'ClassA' => 'Foo\Bar\Baz\ClassA',
                'ClassB' => 'Foo\Bar\Baz\ClassB',
                'ClassD' => 'Foo\Bar\Baz\Fuz\ClassC',
                'functionName1' => 'My\Name\Space\functionName1',
                'func'          => 'My\Name\Space\functionName2',
                'CONSTANT'      => 'My\\Name\\Space\\CONSTANT'
            ]);

            $parsed = Parser::parse($content);
            expect(Parser::unparse($parsed))->toBe($content);

            $parsed = Parser::debug($content);
            expect($parsed)->toBe(file_get_contents($filename . '.txt'));

        });

        it("parses ::class syntax", function () {

            $filename = 'spec/Fixture/Jit/Parser/StaticClassKeyword';
            $content = file_get_contents($filename . '.php');

            $parsed = Parser::debug($content);
            expect($parsed)->toBe(file_get_contents($filename . '.txt'));

            $parsed = Parser::parse($content);
            expect(Parser::unparse($parsed))->toBe($content);

        });

        it("parses anonymous class", function () {

            $filename = 'spec/Fixture/Jit/Parser/AnonymousClass';
            $content = file_get_contents($filename . '.php');

            $parsed = Parser::debug($content);
            expect($parsed)->toBe(file_get_contents($filename . '.txt'));

            $parsed = Parser::parse($content);
            expect(Parser::unparse($parsed))->toBe($content);

        });

        it("parses extends", function () {

            $sample = file_get_contents('spec/Fixture/Jit/Parser/Extends.php');
            $root = Parser::parse($sample);

            $check = 0;

            foreach ($root->tree as $node) {
                if ($node->type !== 'namespace') {
                    continue;
                }
                expect($node->name)->toBe('Test');
                foreach ($node->tree as $node) {
                    if ($node->type !== 'class') {
                        continue;
                    }
                    if ($node->name === 'A') {
                        expect($node->extends)->toBe('\Space\ParentA');
                        $check++;
                    }
                    if ($node->name === 'B') {
                        expect($node->extends)->toBe('\Some\Name\Space\ParentB');
                        $check++;
                    }
                    if ($node->name === 'C') {
                        expect($node->extends)->toBe('\Some\Name\Space');
                        $check++;
                    }
                    if ($node->name === 'D') {
                        expect($node->extends)->toBe('\Test\ParentD');
                        $check++;
                    }
                    if ($node->name === 'E') {
                        expect($node->extends)->toBe('');
                        $check++;
                    }
                }
            }

            expect($check)->toBe(5);
        });

        it("parses implements", function () {

            $sample = file_get_contents('spec/Fixture/Jit/Parser/Implements.php');
            $root = Parser::parse($sample);

            $check = 0;
            ;

            foreach ($root->tree as $node) {
                if ($node->type !== 'namespace') {
                    continue;
                }
                expect($node->name)->toBe('Test');
                foreach ($node->tree as $node) {
                    if ($node->type !== 'class') {
                        continue;
                    }
                    if ($node->name === 'A') {
                        expect($node->implements)->toBe(['\Space\ParentA']);
                        $check++;
                    }
                    if ($node->name === 'B') {
                        expect($node->implements)->toBe(['\Some\Name\Space\ParentB']);
                        $check++;
                    }
                    if ($node->name === 'C') {
                        expect($node->implements)->toBe(['\Test\ParentC']);
                        $check++;
                    }
                    if ($node->name === 'D') {
                        expect($node->implements)->toBe(['\Test\ParentD1', '\Test\ParentD2', '\Test\ParentD3']);
                        $check++;
                    }
                    if ($node->name === 'E') {
                        expect($node->implements)->toBe([]);
                        $check++;
                    }
                }
            }

            expect($check)->toBe(5);
        });

        it("parses declare", function () {

            $filename = 'spec/Fixture/Jit/Parser/DeclareStrictTypes';
            $content = file_get_contents($filename . '.php');

            $parsed = Parser::debug($content);
            expect($parsed)->toBe(file_get_contents($filename . '.txt'));

            $parsed = Parser::parse($content);
            expect(Parser::unparse($parsed))->toBe($content);
        });

        it("parses declare as block", function () {

            $filename = 'spec/Fixture/Jit/Parser/DeclareTicksAsBlock';
            $content = file_get_contents($filename . '.php');

            $parsed = Parser::debug($content);
            expect($parsed)->toBe(file_get_contents($filename . '.txt'));

            $parsed = Parser::parse($content);
            expect(Parser::unparse($parsed))->toBe($content);
        });

        it("parses interfaces", function () {

            $filename = 'spec/Fixture/Jit/Parser/Interface';
            $content = file_get_contents($filename . '.php');

            $parsed = Parser::debug($content);
            expect($parsed)->toBe(file_get_contents($filename . '.txt'));

            $parsed = Parser::parse($content);
            expect(Parser::unparse($parsed))->toBe($content);
        });

        it("parses alternative control structures as dead code", function () {

            $filename = 'spec/Fixture/Jit/Parser/AlternativeControlStructures';
            $content = file_get_contents($filename . '.php');

            $parsed = Parser::debug($content);
            expect($parsed)->toBe(file_get_contents($filename . '.txt'));

            $parsed = Parser::parse($content);
            expect(Parser::unparse($parsed))->toBe($content);
        });

        it("parses named arguments", function () {

            $filename = 'spec/Fixture/Jit/Parser/NamedArguments';
            $content = file_get_contents($filename . '.php');

            $parsed = Parser::debug($content);
            expect($parsed)->toBe(file_get_contents($filename . '.txt'));

            $parsed = Parser::parse($content);
            expect(Parser::unparse($parsed))->toBe($content);
        });

        it("parses constructor promotion", function () {

            $filename = 'spec/Fixture/Jit/Parser/ConstructorPromotion';
            $content = file_get_contents($filename . '.php');

            $parsed = Parser::debug($content);
            expect($parsed)->toBe(file_get_contents($filename . '.txt'));

            $parsed = Parser::parse($content);
            expect(Parser::unparse($parsed))->toBe($content);
        });

        it("parses annotation attributes", function () {

            skipIf(PHP_MAJOR_VERSION < 8);

            $filename = 'spec/Fixture/Jit/Parser/AnnotationAttributes';
            $content = file_get_contents($filename . '.php');

            $parsed = Parser::debug($content);
            expect($parsed)->toBe(file_get_contents($filename . '.txt'));
            $parsed = Parser::parse($content);
            expect(Parser::unparse($parsed))->toBe($content);
        });

        it("parses annotation attributes with default values", function () {

            skipIf(PHP_MAJOR_VERSION < 8);

            $filename = 'spec/Fixture/Jit/Parser/AnnotationAttributesWithDefaultValues';
            $content = file_get_contents($filename . '.php');

            $parsed = Parser::debug($content);
            expect($parsed)->toBe(file_get_contents($filename . '.txt'));
            $parsed = Parser::parse($content);
            expect(Parser::unparse($parsed))->toBe($content);

        });

        it("parses annotation attributes on multiple lines", function () {

            skipIf(PHP_MAJOR_VERSION < 8);

            $filename = 'spec/Fixture/Jit/Parser/AnnotationAttributesOnMultipleLines';
            $content = file_get_contents($filename . '.php');

            $parsed = Parser::debug($content);
            expect($parsed)->toBe(file_get_contents($filename . '.txt'));
            $parsed = Parser::parse($content);
            expect(Parser::unparse($parsed))->toBe($content);

        });

        it("parses multiple annotation attributes in single line", function () {

            skipIf(PHP_MAJOR_VERSION < 8);

            $filename = 'spec/Fixture/Jit/Parser/MultipleAnnotationAttributesInSingleLine';
            $content = file_get_contents($filename . '.php');

            $parsed = Parser::debug($content);
            expect($parsed)->toBe(file_get_contents($filename . '.txt'));
            $parsed = Parser::parse($content);
            expect(Parser::unparse($parsed))->toBe($content);

        });

    });

});
