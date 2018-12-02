<?php
namespace Kahlan\Spec\Suite\Block;

use Kahlan\Block\Group;

describe("Group", function () {

    beforeEach(function () {

        $this->group = new Group();

    });

    describe("->partition()", function () {

        it("splits the specs", function () {

            $this->group->describe("describe1", function () {
                $this->it("it1", function () {
                });
            });

            $this->group->describe("describe2", function () {
                $this->it("it2", function () {
                });
            });

            $part1 = [];
            $this->group->partition(1, 2);

            foreach ($this->group->children() as $key => $children) {
                $part1[$key] = $children->enabled();
            }

            $part1 = array_filter($part1);
            expect($part1)->toHaveLength(1);

            $part2 = [];
            $this->group->partition(2, 2);

            foreach ($this->group->children() as $key => $children) {
                $part2[$key] = $children->enabled();
            }

            $part2 = array_filter($part2);
            expect($part2)->toHaveLength(1);

            $all = $part1 + $part2;
            expect($all)->toHaveLength(2);

        });

    });

});
