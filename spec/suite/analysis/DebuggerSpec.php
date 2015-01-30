<?php
namespace kahlan\spec\suite\analysis;

use Exception;
use kahlan\analysis\Debugger;
use kahlan\plugin\Stub;

ddescribe("Debugger", function() {

	describe("->config()", function() {

		it("should set config properly", function() {

			$debugger = new Debugger();
			$debugger->config([
				'classes' => ['list', 'of', 'some', 'classes']
			]);
			expect(Debugger::$_classes)->toBe(['list', 'of', 'some', 'classes']);

		});

	});

	describe("::trace", function() {

		it("returns a default backtrace string", function() {

			$backtrace = Debugger::trace();
			expect($backtrace)->toBeA('string');

			$backtrace = explode("\n", $backtrace);
			expect($backtrace)->toHaveLength(14);

		});

		it("returns a custom backtrace string", function() {

			$backtrace = Debugger::trace(['trace' => debug_backtrace()]);
			expect($backtrace)->toBeA('string');

			$backtrace = explode("\n", $backtrace);
			expect($backtrace)->toHaveLength(12);

		});

		it("returns a backtrace of an Exception", function() {

			$backtrace = Debugger::trace(['trace' => new Exception('World Destruction Error!')]);
			expect($backtrace)->toBeA('string');

			$backtrace = explode("\n", $backtrace);
			expect($backtrace)->toHaveLength(13);

		});

	});

	describe("::message", function() {

		it("returns the message of an exception", function() {

			$message = Debugger::message(new Exception('World Destruction Error!'));
			expect($message)->toBe('`Exception` Code(0): World Destruction Error!');

		});

		it("return backtrace if it's not an exception instance", function() {

			$backtrace = [
				'message' => 'World Destruction Error!',
				'code'    => 404
			];

			$message = Debugger::message($backtrace);
			expect($message)->toBe("`<INVALID>` Code(404): World Destruction Error!");

		});

	});


    describe("::errorType", function() {

        it("returns some reader-friendly error type string", function() {

            expect(Debugger::errorType(E_ERROR))->toBe('E_ERROR');
            expect(Debugger::errorType(E_WARNING))->toBe('E_WARNING');
            expect(Debugger::errorType(E_PARSE))->toBe('E_PARSE');
            expect(Debugger::errorType(E_NOTICE))->toBe('E_NOTICE');
            expect(Debugger::errorType(E_CORE_ERROR))->toBe('E_CORE_ERROR');
            expect(Debugger::errorType(E_CORE_WARNING))->toBe('E_CORE_WARNING');
            expect(Debugger::errorType(E_CORE_ERROR))->toBe('E_CORE_ERROR');
            expect(Debugger::errorType(E_COMPILE_ERROR))->toBe('E_COMPILE_ERROR');
            expect(Debugger::errorType(E_CORE_WARNING))->toBe('E_CORE_WARNING');
            expect(Debugger::errorType(E_COMPILE_WARNING))->toBe('E_COMPILE_WARNING');
            expect(Debugger::errorType(E_USER_ERROR))->toBe('E_USER_ERROR');
            expect(Debugger::errorType(E_USER_WARNING))->toBe('E_USER_WARNING');
            expect(Debugger::errorType(E_USER_NOTICE))->toBe('E_USER_NOTICE');
            expect(Debugger::errorType(E_STRICT))->toBe('E_STRICT');
            expect(Debugger::errorType(E_RECOVERABLE_ERROR))->toBe('E_RECOVERABLE_ERROR');
            expect(Debugger::errorType(E_DEPRECATED))->toBe('E_DEPRECATED');
            expect(Debugger::errorType(E_USER_DEPRECATED))->toBe('E_USER_DEPRECATED');

        });

		it("returns <INVALID> for undefined error type", function() {

			expect(Debugger::errorType(123456))->toBe('<INVALID>');

		});

    });

});
