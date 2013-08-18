<?php
namespace spec;

use kahlan\analysis\TokenStream;
use Exception;

describe("TokenStream", function() {

	beforeEach(function() {
		$this->code = <<<EOD
<?php
class HelloWorld {
	/**
	 * Echoing Hello World
	 */
	public function hello() {
		\$echo = function(\$msg) { echo \$msg };
		\$echo('Hello World');
	}
}
?>
EOD;
		$this->stream = new TokenStream(['source' => $this->code]);
		$this->len = count(token_get_all($this->code));
	});

	describe("iterating", function() {

		describe("::current()", function() {

			it("should get the current token value", function() {
				$value = $this->stream->current();
				$this->expect($value)->toBe("<?php\n");
			});

			it("should get the current token", function() {
				$value = $this->stream->current(true);
				$this->expect($value)->toBe([T_OPEN_TAG, "<?php\n", 1]);
			});

		});

		describe("::next()", function() {

			it("should move next", function() {
				$key = $this->stream->key();
				$this->stream->next();
				$this->expect($key)->not->toBe($this->stream->key());
			});

			it("should get the next token value", function() {
				$value = $this->stream->next();
				$this->expect($value)->toBe("class");
			});

			it("should get the next token", function() {
				$value = $this->stream->next(true);
				$this->expect($value)->toBe([T_CLASS, "class", 2]);
			});

			it("should iterate through all tokens", function() {
				$i = 0;
				foreach ($this->stream as $value) {
					$len = strlen($value);
					$this->expect($value)->toBe(substr($this->code, $i, $len));
					$i += $len;
				}
				$this->expect($i)->toBe(strlen($this->code));
			});

		});

		describe("::next()", function() {

			it("should move prev", function() {
				$key = $this->stream->key();
				$this->stream->next();
				$this->stream->prev();
				$this->expect($key)->not->toBe($this->stream->current());
			});

			it("should get the previous token value", function() {
				$this->stream->seek(1);
				$value = $this->stream->prev();
				$this->expect($value)->toBe("<?php\n");
			});

			it("should get the previous token", function() {
				$this->stream->seek(1);
				$value = $this->stream->prev(true);
				$this->expect($value)->toBe([T_OPEN_TAG, "<?php\n", 1]);
			});

		});

		describe("::key()", function() {

			it("should return the current key", function() {
				$this->expect($this->stream->key())->toBe(0);
				$this->stream->next();
				$this->expect($this->stream->key())->toBe(1);
			});

		});

		describe("::seek()", function() {

			it("should correctly seek inside the stream", function() {
				$this->stream->seek($this->len - 1);
				$this->expect('?>')->toBe($this->stream->current());
			});

		});

		describe("::rewind()", function() {

			it("should reset the stream to the start", function() {
				$key = $this->stream->key();
				$this->stream->next();
				$this->stream->rewind();
				$this->expect($key)->toBe($this->stream->key());
			});

		});

		describe("::valid()", function() {

			it("should return true if the the stream is iteratable", function() {
				$this->expect($this->stream->valid())->toBe(true);
			});

			it("should return false if the the stream is no more iteratable", function() {
				$this->stream->seek($this->len - 1);
				$this->expect($this->stream->valid())->toBe(true);
				$this->stream->next();
				$this->expect($this->stream->valid())->toBe(false);
			});

		});

	});

	describe("::count()", function() {

		it("should return the correct number of tokens", function() {
			$this->expect($this->stream->count())->toBe($this->len);
		});

	});

	describe("::offsetGet()", function() {

		it("should access token by key", function() {
			$key = $this->stream->key();
			$value = $this->stream[$key][1];
			$this->expect($value)->toBe($this->stream->current());
		});

	});

	describe("array access", function() {

		describe("::offsetExist()", function() {

			it("should return true for an existing offset", function() {
				$this->expect(isset($this->stream[0]))->toBe(true);
				$this->expect(isset($this->stream[$this->len - 1]))->toBe(true);
			});

			it("should return false for an unexisting offset", function() {
				$this->expect(isset($this->stream[$this->len]))->toBe(false);
			});

		});

		describe("::offsetSet()", function() {

			it("should throw an exception", function() {
				$this->expect(isset($this->stream[0]))->toBe(true);
				$this->expect(isset($this->stream[$this->len - 1]))->toBe(true);
			});

		});

		describe("::offsetUnset()", function() {

			it("should throw an exception", function() {
				$this->expect(function() {
					unset($this->stream[0]);
				})->toThrow(new Exception);
			});

		});

		describe("::offsetSet()", function() {

			it("should throw an exception", function() {
				$this->expect(function() {
					$this->stream[0] = [];
				})->toThrow(new Exception());
			});

		});
	});

	describe("extracting", function() {

		beforeEach(function() {
			$this->code = <<<EOD
<?php
class TestBrackets {
	public function test1() {
		rand(2,5);
	}
}
?>
EOD;
			$this->stream = new TokenStream(['source' => $this->code]);
			$this->len = count(token_get_all($this->code));
		});

		it("should return the skipped content until the next correponding token", function() {
			$content = $this->stream->next(T_CLASS);
			$this->expect($content)->toBe("class");
		});

		it("should extract the body between two correponding bracket", function() {
			$this->stream->next(T_FUNCTION);
			$this->stream->next('{');
			$body = $this->stream->nextMatchingBracket();
			$this->expect($body)->toBe("{\n\t\trand(2,5);\n\t}");
		});

	});

	describe("token infos", function() {

		describe("::getType()", function() {

			it("should return the correct token type", function() {
				$this->expect($this->stream->getType(0))->toBe(T_OPEN_TAG);
			});

		});

		describe("::getValue()", function() {

			it("should return the correct token value", function() {
				$this->expect($this->stream->getValue(0))->toBe("<?php\n");
			});

		});

		describe("::getName()", function() {

			it("should return the correct token name", function() {
				$this->expect($this->stream->getName(0))->toBe("T_OPEN_TAG");
			});

		});

		describe("::is()", function() {

			it("should return true when type is correct", function() {
				$this->expect( $this->stream->is(T_OPEN_TAG, 0))->toBe(true);
			});

			it("should return false when type is incorrect", function() {
				$this->expect( $this->stream->is(T_OPEN_TAG, 1))->toBe(false);
			});

		});
	});
});

?>