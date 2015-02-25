<?php
namespace kahlan\spec\suite\jit\patcher;

use jit\Parser;
use kahlan\jit\patcher\Layer;

describe("Layer", function() {

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

}<?php class InspectorKLAYER extends \\kahlan\\analysis\\Inspector {    public static function inspect(\$class) {\$__KPOINTCUT_ARGS__ = func_get_args(); \$__KPOINTCUT_SELF__ = isset(\$this) ? \$this : get_called_class(); if (\$__KPOINTCUT__ = \\kahlan\\plugin\\Pointcut::before(__METHOD__, \$__KPOINTCUT_SELF__, \$__KPOINTCUT_ARGS__)) { \$r = \$__KPOINTCUT__(\$__KPOINTCUT_SELF__, \$__KPOINTCUT_ARGS__); return \$r; }return parent::inspect(\$class);}    public static function parameters(\$class, \$method, \$data = NULL) {\$__KPOINTCUT_ARGS__ = func_get_args(); \$__KPOINTCUT_SELF__ = isset(\$this) ? \$this : get_called_class(); if (\$__KPOINTCUT__ = \\kahlan\\plugin\\Pointcut::before(__METHOD__, \$__KPOINTCUT_SELF__, \$__KPOINTCUT_ARGS__)) { \$r = \$__KPOINTCUT__(\$__KPOINTCUT_SELF__, \$__KPOINTCUT_ARGS__); return \$r; }return parent::parameters(\$class, \$method, \$data);}    public static function typehint(\$parameter) {\$__KPOINTCUT_ARGS__ = func_get_args(); \$__KPOINTCUT_SELF__ = isset(\$this) ? \$this : get_called_class(); if (\$__KPOINTCUT__ = \\kahlan\\plugin\\Pointcut::before(__METHOD__, \$__KPOINTCUT_SELF__, \$__KPOINTCUT_ARGS__)) { \$r = \$__KPOINTCUT__(\$__KPOINTCUT_SELF__, \$__KPOINTCUT_ARGS__); return \$r; }return parent::typehint(\$parameter);}}?>

EOD;

            expect($actual)->toBe($expected);

        });

    });

});
