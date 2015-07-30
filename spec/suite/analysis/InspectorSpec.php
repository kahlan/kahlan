<?php
namespace kahlan\spec\suite\analysis;

use kahlan\analysis\Inspector;

describe("Inspector", function() {

    before(function() {
        $this->class = 'kahlan\spec\fixture\analysis\SampleClass';
    });

    describe('::inspect()', function() {

        it("gets the reflexion layer of a class", function() {

            $inspector = Inspector::inspect($this->class);
            expect($inspector)->toBeAnInstanceOf('ReflectionClass');
            expect($inspector->name)->toBe($this->class);

        });

    });

    describe('::parameters()', function() {

        it("gets method's parameters details", function() {

            $inspector = Inspector::parameters($this->class, 'parametersExample');
            expect($inspector)->toBeA('array');

            $param2 = next($inspector);
            expect($param2)->toBeAnInstanceOf('ReflectionParameter');
            expect($param2->getName())->toBe('b');
            expect($param2->getDefaultValue())->toBe(100);

            $param3 = next($inspector);
            expect($param3)->toBeAnInstanceOf('ReflectionParameter');
            expect($param3->getName())->toBe('c');
            expect($param3->getDefaultValue())->toBe('abc');

            $param4 = next($inspector);
            expect($param4)->toBeAnInstanceOf('ReflectionParameter');
            expect($param4->getName())->toBe('d');
            expect($param4->getDefaultValue())->toBe(null);

        });

        it("merges defauts values with populated values when the third argument is not empty", function() {

            $inspector = Inspector::parameters($this->class, 'parametersExample', [
                'first',
                1000,
                true
            ]);

            expect($inspector)->toBe([
                'a' => 'first',
                'b' => 1000,
                'c' => true,
                'd' => null
            ]);

        });

    });

    describe("::typehint()", function() {

        it("returns an empty string when no typehint is present", function() {

            $inspector = Inspector::parameters($this->class, 'parametersExample');
            expect(Inspector::typehint($inspector[0]))->toBe('');

            $inspector = Inspector::parameters($this->class, 'parameterByReference');
            expect(Inspector::typehint($inspector[0]))->toBe('');

        });

        it("returns parameter typehint", function() {

            $inspector = Inspector::parameters($this->class, 'exceptionTypeHint');
            $typehint = Inspector::typehint(current($inspector));
            expect($typehint)->toBeA('string');
            expect($typehint)->toBe('\Exception');

            $inspector = Inspector::parameters($this->class, 'arrayTypeHint');
            $typehint = Inspector::typehint(current($inspector));
            expect($typehint)->toBeA('string');
            expect($typehint)->toBe('array');

            $inspector = Inspector::parameters($this->class, 'callableTypeHint');
            $typehint = Inspector::typehint(current($inspector));
            expect($typehint)->toBeA('string');
            expect($typehint)->toBe('callable');

        });

    });

});
