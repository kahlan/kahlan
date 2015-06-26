<?php
namespace kahlan\spec\suite\matcher;

use stdClass;
use kahlan\spec\mock\Collection;
use kahlan\spec\mock\Traversable;
use kahlan\matcher\ToContainKey;

describe("toContainKey", function() {

    describe("::match()", function() {

        context("with an array", function() {

            it("passes if 2 key is in [1, 2, 3]", function() {

                expect([1, 2, 3])->toContainKey(2);

            });

            it("passes if 'a' key is in ['a', 'b', 'c']", function() {

                expect(['a' => 1, 'b' => 2, 'c' => 3])->toContainKey('a');

            });

            it("passes when we expect a array ['a', 'b'] key is in ['a', 'b', 'c']", function() {

                expect(['a' => 1, 'b' => 2, 'c' => 3])->toContainKeys(['a', 'b']);

            });

            it("passes when we pass a function args 'a', 'b' key is in ['a', 'b', 'c']", function() {

                expect(['a' => 1, 'b' => 2, 'c' => 3])->toContainKeys('a', 'b');

            });

            it("passes if 'd' key is not in ['a', 'b', 'c']", function() {

                expect(['a' => 1, 'b' => 2, 'c' => 3])->not->toContainKey('d');

            });

            it("passes if ['a', 'b', 'd'] keys is not in ['a', 'b', 'c']", function() {

                expect(['a' => 1, 'b' => 2, 'c' => 3])->not->toContainKeys(['a', 'b', 'd']);

            });

            it("passes if 'a', 'b', 'd' params keys is not in ['a', 'b', 'c']", function() {

                expect(['a' => 1, 'b' => 2, 'c' => 3])->not->toContainKeys('a', 'b', 'd');

            });

        });

        context("with a collection instance", function() {

            it("passes if 2 key is in [1, 2, 3]", function() {

                expect(new Collection(['data' => [1, 2, 3]]))->toContainKey(2);

            });

            it("passes if 'a' key is in ['a', 'b', 'c']", function() {

                expect(new Collection(['data' => ['a' => 1, 'b' => 2, 'c' => 3]]))->toContainKey('a');

            });

            it("passes if 'd' key is not in ['a', 'b', 'c']", function() {

                expect(new Collection(['data' => ['a' => 1, 'b' => 2, 'c' => 3]]))->not->toContainKey('d');

            });

            it("passes if ['a', 'd'] key is not in ['a', 'b', 'c']", function() {

                expect(new Collection(['data' => ['a' => 1, 'b' => 2, 'c' => 3]]))->not->toContainKeys(['a', 'd']);

            });

            it("passes if 'a', 'd' params is not in ['a', 'b', 'c']", function() {

                expect(new Collection(['data' => ['a' => 1, 'b' => 2, 'c' => 3]]))->not->toContainKeys('a', 'd');

            });

        });

        context("with a traversable instance", function() {

            it("passes if 2 key is in [1, 2, 3]", function() {

                expect(new Traversable(['data' => [1, 2, 3]]))->toContainKey(2);

            });

            it("passes if 'a' key is in ['a', 'b', 'c']", function() {

                expect(new Traversable(['data' => ['a' => 1, 'b' => 2, 'c' => 3]]))->toContainKey('a');

            });

            it("passes if 'd' key is not in ['a', 'b', 'c']", function() {

                expect(new Traversable(['data' => ['a' => 1, 'b' => 2, 'c' => 3]]))->not->toContainKey('d');

            });

            it("passes if ['a', 'd'] key is not in ['a', 'b', 'c']", function() {

                expect(new Traversable(['data' => ['a' => 1, 'b' => 2, 'c' => 3]]))->not->toContainKeys(['a', 'd']);

            });

            it("passes if 'a', 'd' params is not in ['a', 'b', 'c']", function() {

                expect(new Traversable(['data' => ['a' => 1, 'b' => 2, 'c' => 3]]))->not->toContainKeys('a', 'd');

            });

        });

        it("fails with non array/collection/traversable", function() {

            expect(new stdClass())->not->toContainKey('key');
            expect(false)->not->toContainKey('0');
            expect(true)->not->toContainKey('1');

        });

    });

    describe("::description()", function() {

        it("returns the description message", function() {

            $actual = ToContainKey::description();

            expect($actual)->toBe('contain expected key.');

        });

    });

});
