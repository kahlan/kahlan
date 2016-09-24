<?php
namespace Kahlan\Spec\Suite\Reporter\Coverage;

use Kahlan\Reporter\Terminal;

describe("Terminal", function () {
    
    beforeEach(function () {
        $this->terminal = new Terminal([]);
    });

    describe("->kahlan()", function () {

        it("return kahlan motd", function () {
            $kahlan = <<<EOD
            _     _
  /\ /\__ _| |__ | | __ _ _ __
 / //_/ _` | '_ \| |/ _` | '_ \
/ __ \ (_| | | | | | (_| | | | |
\/  \/\__,_|_| |_|_|\__,_|_| |_|
EOD;
            $result = $this->terminal->kahlan();
            expect($result)->toBe($kahlan);
        });

    });

    describe("->indent", function () {

        it("return indent", function () {
            $indent = '    ';

            $result = $this->terminal->indent($indent);
            expect($result)->toBe($indent);
        });

    });

});
