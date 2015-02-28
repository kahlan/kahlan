<?php
namespace kahlan\spec\suite;

use Exception;
use kahlan\Report;

describe("Report", function() {

    it("should create empty report", function() {

        $report = new Report();
        expect($report->scope())->toBe(null);
        expect($report->type())->toBe("pass");
        expect($report->not())->toBe(false);
        expect($report->description())->toBe(null);
        expect($report->matcher())->toBe(null);
        expect($report->matcherName())->toBe(null);
        expect($report->params())->toBe([]);
        expect($report->backtrace())->toBe([]);
        expect($report->exception())->toBe(null);
        expect($report->file())->toBe(null);
        expect($report->line())->toBe(null);
        expect($report->childs())->toBe([]);

    });

});