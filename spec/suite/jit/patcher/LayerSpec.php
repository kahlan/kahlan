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

}class InspectorKLAYER extends \\kahlan\\analysis\\Inspector {    public static function inspect(\$class) {return parent::inspect(\$class);}    public static function parameters(\$class, \$method, \$data = NULL) {return parent::parameters(\$class, \$method, \$data);}    public static function typehint(\$parameter) {return parent::typehint(\$parameter);}}

EOD;

            expect($actual)->toBe($expected);

        });

    });

});
