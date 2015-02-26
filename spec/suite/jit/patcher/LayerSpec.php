<?php
namespace kahlan\spec\suite\jit\patcher;

use jit\Parser;
use kahlan\jit\patcher\Layer;

describe("Layer", function() {

    describe("->file()", function() {

        it("should return file", function() {

            $layer = new Layer();
            expect($layer->findFile(null, null, 'some_file_name'))->toBe('some_file_name');

        });

    });

    describe("->process()", function() {

        beforeEach(function() {
            $this->path = 'spec/fixture/jit/patcher/layer';
            $this->patcher = new Layer([
                'override' => [
                    'kahlan\analysis\Inspector'
                ]
            ]);
        });

        it("patches class's extends", function() {

            $nodes = Parser::parse(file_get_contents($this->path . '/Layer.php'));
            $actual = Parser::unparse($this->patcher->process($nodes));

            $expected = <<<EOD
<?php
namespace kahlan\\spec\\fixture\\jit\\patcher\\layer;

class Inspector extends InspectorKLAYER
{

}class InspectorKLAYER extends \\kahlan\\analysis\\Inspector {    public static function inspect(\$class) {\$__KPOINTCUT_ARGS__ = func_get_args(); \$__KPOINTCUT_SELF__ = isset(\$this) ? \$this : get_called_class(); if (\$__KPOINTCUT__ = \\kahlan\\plugin\\Pointcut::before(__METHOD__, \$__KPOINTCUT_SELF__, \$__KPOINTCUT_ARGS__)) { \$r = \$__KPOINTCUT__(\$__KPOINTCUT_SELF__, \$__KPOINTCUT_ARGS__); return \$r; }return parent::inspect(\$class);}    public static function parameters(\$class, \$method, \$data = NULL) {\$__KPOINTCUT_ARGS__ = func_get_args(); \$__KPOINTCUT_SELF__ = isset(\$this) ? \$this : get_called_class(); if (\$__KPOINTCUT__ = \\kahlan\\plugin\\Pointcut::before(__METHOD__, \$__KPOINTCUT_SELF__, \$__KPOINTCUT_ARGS__)) { \$r = \$__KPOINTCUT__(\$__KPOINTCUT_SELF__, \$__KPOINTCUT_ARGS__); return \$r; }return parent::parameters(\$class, \$method, \$data);}    public static function typehint(\$parameter) {\$__KPOINTCUT_ARGS__ = func_get_args(); \$__KPOINTCUT_SELF__ = isset(\$this) ? \$this : get_called_class(); if (\$__KPOINTCUT__ = \\kahlan\\plugin\\Pointcut::before(__METHOD__, \$__KPOINTCUT_SELF__, \$__KPOINTCUT_ARGS__)) { \$r = \$__KPOINTCUT__(\$__KPOINTCUT_SELF__, \$__KPOINTCUT_ARGS__); return \$r; }return parent::typehint(\$parameter);}}

EOD;

            expect($actual)->toBe($expected);

        });
        
        it("just skip if no override is set", function() {

            $this->patcher = new Layer([]);
            $nodes = Parser::parse(file_get_contents($this->path . '/Layer.php'));
            $actual = Parser::unparse($this->patcher->process($nodes));

            expect($actual)->toBe("");

        });

        it("skips if no need in override", function() {

            $this->patcher = new Layer([
                'override' => [
                    'kahlan\analysis\Debugger'
                ]
            ]);

            $nodes = Parser::parse(file_get_contents($this->path . '/Layer.php'));
            $actual = Parser::unparse($this->patcher->process($nodes));
            $expected = <<<EOD
<?php
namespace kahlan\\spec\\fixture\\jit\\patcher\\layer;

class Inspector extends \\kahlan\\analysis\\Inspector
{

}

EOD;
            expect($actual)->toBe($expected);

        });

    });

});
