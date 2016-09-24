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

    describe("->indent()", function () {

        it("return indent", function () {
            $indent = '    ';

            $result = $this->terminal->indent($indent);
            expect($result)->toBe($indent);
        });

    });

    describe("->prefix()", function () {

        it("return prefix", function () {
            $prefix = 'prefix';

            $result = $this->terminal->prefix($prefix);
            expect($result)->toBe($prefix);
        });

    });

    describe("->readableSize()", function () {

        it("return 0 when value is < 1", function () {
            $readableSize = '0';

            $result = $this->terminal->readableSize(0, 2, 1024);
            expect($result)->toBe($readableSize);
        });

        it("return round precision unit when value is >= 1", function () {
            $readableSize = '10';

            $result = $this->terminal->readableSize(10, 2, 1024);
            expect($result)->toBe($readableSize);
        });

        it("return round precision unit when value is >= 1 with value > base with loop value / base", function () {
            $readableSize = '9.77K';

            $result = $this->terminal->readableSize(10000, 2, 1024);
            expect($result)->toBe($readableSize);
        });

    });

});
