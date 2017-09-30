<?php
namespace Kahlan\Spec\Suite\Plugin;

use Exception;
use DateTime;
use Kahlan\Jit\ClassLoader;
use Kahlan\Plugin\Monkey;
use Kahlan\Jit\Patcher\Monkey as MonkeyPatcher;

use Kahlan\Spec\Fixture\Plugin\Monkey\Mon;

function mytime()
{
    return 245026800;
}

function myrand($min, $max)
{
    return 101;
}

describe("Monkey", function () {

    beforeAll(function () {
        $cachePath = rtrim(sys_get_temp_dir(), DS) . DS . 'kahlan';
        $include = ['Kahlan\Spec\\'];
        $this->loader = new ClassLoader();
        $this->loader->patch(compact('include', 'cachePath'));
        $this->loader->patchers()->add('monkey', new MonkeyPatcher());
        $this->loader->addPsr4('Kahlan\\', 'src');
        $this->loader->addPsr4('Kahlan\Spec\\', 'spec');
        $this->loader->register(true);
    });

    afterAll(function () {
        $this->loader->unregister();
    });

    it("patches a core function", function () {

        $mon = new Mon();
        Monkey::patch('time', 'Kahlan\Spec\Suite\Plugin\mytime');
        expect($mon->time())->toBe(245026800);

    });

    describe("::patch()", function () {

        it("patches a core function with a closure", function () {

            $mon = new Mon();
            Monkey::patch('time', function () {
                return 123;
            });
            expect($mon->time())->toBe(123);

        });

        it("patches a core class", function () {

            $mon = new Mon();
            Monkey::patch('DateTime', 'Kahlan\Spec\Mock\Plugin\Monkey\MyDateTime');
            expect($mon->datetime()->getTimestamp())->toBe(245026800);

        });

        it("patches a core class using substitutes", function () {

            skipIf(PHP_MAJOR_VERSION < 7);

            $mon = new Mon();
            $patch = Monkey::patch('DateTime');
            $patch->toBe(new DateTime('@123'), new DateTime('@456'));
            expect($mon->datetime()->getTimestamp())->toBe(123);
            expect($mon->datetime()->getTimestamp())->toBe(456);

        });

        it("patches a function", function () {

            $mon = new Mon();
            Monkey::patch('Kahlan\Spec\Fixture\Plugin\Monkey\rand', 'Kahlan\Spec\Suite\Plugin\myrand');
            expect($mon->rand(0, 100))->toBe(101);

        });

        it("patches a class", function () {

            $mon = new Mon();
            Monkey::patch('Kahlan\Util\Text', 'Kahlan\Spec\Mock\Plugin\Monkey\MyString');
            expect($mon->dump((object)'hello'))->toBe('myhashvalue');

        });

        it("can unpatch a monkey patch", function () {

            $mon = new Mon();
            Monkey::patch('Kahlan\Spec\Fixture\Plugin\Monkey\rand', 'Kahlan\Spec\Suite\Plugin\myrand');
            expect($mon->rand(0, 100))->toBe(101);

            Monkey::reset('Kahlan\Spec\Fixture\Plugin\Monkey\rand');
            expect($mon->rand(0, 100))->toBe(50);

        });

        it("throws an exception with trying to patch an unsupported functions or core langage statements", function () {

            $closure = function () {
                Monkey::patch('func_get_args', function () {
                    return [];
                });
            };

            expect($closure)->toThrow(new Exception('Monkey patching `func_get_args()` is not supported by Kahlan.'));
        });

    });

});
