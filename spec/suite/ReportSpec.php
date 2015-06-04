<?php
namespace kahlan\spec\suite;

use Exception;
use kahlan\Report;
use kahlan\Scope;

describe("Report", function() {

    describe("->__construct()", function() {

        it("correctly sets default values", function() {

            $report = new Report();
            expect($report->scope())->toBe(null);
            expect($report->type())->toBe('pass');
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

    describe("->add()", function() {

        beforeEach(function() {
            $this->scope = new Scope();
            $this->pattern = '*Suite.php';
            $this->regExp = strtr(preg_quote($this->pattern, '~'), ['\*' => '.*', '\?' => '.']);
            $this->scope->backtraceFocus($this->pattern);
            $this->reports = new Report([
                "scope" => $this->scope
            ]);
        });

        it("rebases backtrace on fail report", function() {

            $this->reports->add('fail', [
                'backtrace' => debug_backtrace()
            ]);

            $logs = $this->reports->childs();
            $report = $logs[0];
            expect($report->backtrace()[0]['file'])->toMatch("~^{$this->regExp}$~");

        });

        it("doesn't rebase backtrace on an exception report", function() {

            $this->reports->add('exception', [
                'exception' => new Exception()
            ]);

            $logs = $this->reports->childs();
            $report = $logs[0];
            expect($report->backtrace()[0]['file'])->not->toMatch("~^{$this->regExp}$~");

        });

    });

});