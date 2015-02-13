<?php
namespace kahlan\spec\suite\analysis;

use kahlan\analysis\Inspector;

describe("Inspector", function() {

    before(function() {
        $this->class = 'kahlan\spec\fixture\analysis\DummyClass';
    });

    it("should create class inspection", function() {

        $inspector = Inspector::inspect($this->class);
        expect($inspector)->toBeAnInstanceOf('ReflectionClass');
        expect($inspector->name)->toBe($this->class);

    });

    it("should retrieve params of reflection class", function() {

        $inspector = Inspector::parameters($this->class, 'rand');
        expect($inspector)->toBeA('array');

        expect($inspector[0])->toBeAnInstanceOf('ReflectionParameter');
        expect(isset($inspector[0]->name))->toBe(true);
        expect($inspector[0]->name)->toBe('min');

        expect($inspector[1])->toBeAnInstanceOf('ReflectionParameter');
        expect(isset($inspector[1]->name))->toBe(true);
        expect($inspector[1]->name)->toBe('max');

    });

    it("should retrieve params of reflection class with slice", function() {

        $inspector = Inspector::parameters($this->class, 'rand', ['min' => 1000]);
        expect($inspector)->toBeA('array');

        expect(array_keys($inspector))->toContain('min')->toContain('max');
        expect($inspector['min'])->toBe(1000);

    });

    describe("->typehint()", function() {
        it("should return no typehint", function() {

            $inspector = Inspector::parameters($this->class, 'rand');

            $minParameter = $inspector[0];
            $maxParameter = $inspector[1];

            $typehint = Inspector::typehint($minParameter);
            expect($typehint)->toBe("");

        });

        it("should return class typehint", function() {

            $inspector = Inspector::parameters($this->class, 'getTrace');
            $typehint = Inspector::typehint(current($inspector));
            expect($typehint)->toBeA('string');
            expect(trim($typehint))->toBe('Exception');

        });

        it("should return array typehint", function() {

            $inspector = Inspector::parameters($this->class, 'max');
            $typehint = Inspector::typehint(current($inspector));
            expect($typehint)->toBeA('string');
            expect(trim($typehint))->toBe('array');

        });

        it("should return other hinting", function() {

            $inspector = Inspector::parameters($this->class, 'is_number');
            $typehint = Inspector::typehint(current($inspector));
            expect($typehint)->toBeA('string');
            expect(trim($typehint))->toBe('callable');

        });

    });

});
