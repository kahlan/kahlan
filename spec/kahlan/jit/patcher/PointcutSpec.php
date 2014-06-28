<?php
namespace spec\jit\patcher;

use kahlan\analysis\Parser;
use kahlan\jit\patcher\Pointcut;

describe("Pointcut::process", function() {

    beforeEach(function() {
        $this->path = 'spec/fixture/jit/patcher/pointcut';
        $this->patcher = new Pointcut();
    });

    it("adds an entry point to methods and wrap function call", function() {
        $nodes = Parser::parse(file_get_contents($this->path . '/Simple.php'));
        $expected = file_get_contents($this->path . '/SimpleProcessed.php');
        $actual = Parser::unparse($this->patcher->process($nodes));
        expect($actual)->toBe($expected);
    });

});

?>