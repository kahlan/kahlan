<?php
namespace Kahlan\Spec\Suite\Block;

use Kahlan\Arg;
use Kahlan\Block\Builder\CasesBuilder;
use Kahlan\Block\Group;
use Kahlan\Scope\Specification;
use Kahlan\Plugin\Double;

describe('CasesBuilder', function () {

    $this->cases = [
        'first case' => ['a', 1],
        'second case' => ['b', 2],
        42 => ['c', 3],
    ];

    beforeEach(function () {

        $this->group = new Group();

    });

    it('should add specs for each array pair', function () {

        $builder = new CasesBuilder($this->group, $this->cases, null, 'normal');
        $builder->it('should receive');
        $children = $this->group->children();

        expect($children)->toHaveLength(3);

        expect($children[0]->message())->toBe('it should receive first case');
        expect($children[0]->timeout())->toBe(0);
        expect($children[0]->type())->toBe('normal');

        expect($children[1]->message())->toBe('it should receive second case');
        expect($children[1]->timeout())->toBe(0);
        expect($children[1]->type())->toBe('normal');

        expect($children[2]->message())->toBe('it should receive 42');
        expect($children[2]->timeout())->toBe(0);
        expect($children[2]->type())->toBe('normal');

    });

    it('should add specs for each iterator pair', function () {

        $builder = new CasesBuilder($this->group, new \ArrayIterator($this->cases), 10, 'focus');
        $builder->it('should receive');
        $children = $this->group->children();

        expect($children)->toHaveLength(3);

        expect($children[0]->message())->toBe('it should receive first case');
        expect($children[0]->timeout())->toBe(10);
        expect($children[0]->type())->toBe('focus');

        expect($children[1]->message())->toBe('it should receive second case');
        expect($children[1]->timeout())->toBe(10);
        expect($children[1]->type())->toBe('focus');

        expect($children[2]->message())->toBe('it should receive 42');
        expect($children[2]->timeout())->toBe(10);
        expect($children[2]->type())->toBe('focus');

    });

    it('should call closure with case params', function () {

        $closure = Double::instance();
        $boundClosure = Double::instance();

        allow($closure)
            ->toReceive('bindTo')
            ->with(Arg::toBeAnInstanceOf(Specification::class))
            ->andReturn($boundClosure);

        expect($boundClosure)
            ->toReceive('__invoke')
            ->with('a', 1)
            ->ordered;

        expect($boundClosure)
            ->toReceive('__invoke')
            ->with('b', 2)
            ->ordered;

        expect($boundClosure)
            ->toReceive('__invoke')
            ->with('c', 3)
            ->ordered;

        $builder = new CasesBuilder($this->group, $this->cases, null, 'normal');
        $builder->it('should receive', $closure);

        $this->group->process();

    });

    it('should do nothing with empty cases', function () {

        $builder = new CasesBuilder($this->group, [], null, 'normal');
        $builder->it('should receive');

        expect($this->group->children())->toHaveLength(0);

    });

});
