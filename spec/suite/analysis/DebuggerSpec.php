<?php
namespace kahlan\spec\suite\analysis;

use Exception;
use kahlan\analysis\Debugger;
use kahlan\plugin\Stub;

describe("Debugger", function() {

	describe("::trace()", function() {

		it("returns a default backtrace string", function() {

			$backtrace = Debugger::trace();
			expect($backtrace)->toBeA('string');

			$backtrace = explode("\n", $backtrace);
			expect(empty($backtrace))->toBe(false);

		});

		it("returns a custom backtrace string", function() {

			$backtrace = Debugger::trace(['trace' => debug_backtrace()]);
			expect($backtrace)->toBeA('string');

			$backtrace = explode("\n", $backtrace);
			expect(empty($backtrace))->toBe(false);

		});

		it("returns a backtrace of an Exception", function() {

			$backtrace = Debugger::trace(['trace' => new Exception('World Destruction Error!')]);
			expect($backtrace)->toBeA('string');

			$backtrace = explode("\n", $backtrace);
			expect(empty($backtrace))->toBe(false);

		});

	});

	describe("::message()", function() {

		it("formats an exception as a string message", function() {

			$message = Debugger::message(new Exception('World Destruction Error!'));
			expect($message)->toBe('`Exception` Code(0): World Destruction Error!');

		});

		it("formats a backtrace array as a string message", function() {

			$backtrace = [
				'message' => 'E_ERROR Error!',
				'code'    => E_ERROR
			];

			$message = Debugger::message($backtrace);
			expect($message)->toBe("`E_ERROR` Code(1): E_ERROR Error!");

<<<<<<< HEAD
			$exception = new \Exception("World Destruction Error!", 404);
			$message = Debugger::message([
				'message' => $exception->getMessage(),
				'code' => $exception->getCode(),
			]);
			expect($message)->toBe("`<INVALID>` Code(404): World Destruction Error!");
=======
			$backtrace = [
				'message' => 'Invalid Error!',
				'code'    => 404
			];

			$message = Debugger::message($backtrace);
			expect($message)->toBe("`<INVALID>` Code(404): Invalid Error!");
>>>>>>> dev

		});

	});


    describe("::errorType()", function() {

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
