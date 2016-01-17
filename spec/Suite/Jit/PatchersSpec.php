<?php
namespace Kahlan\Spec\Jit\Suite;

use Kahlan\Arg;
use Kahlan\Plugin\Stub;

use Kahlan\Jit\Patchers;

describe("Patchers", function() {

    beforeEach(function(){
        $this->patchers = new Patchers;
    });

    describe("->add/get()", function() {

        it("stores a patcher", function() {

            $stub = Stub::create();
            $this->patchers->add('my_patcher', $stub);

            $actual = $this->patchers->get('my_patcher');
            expect($actual)->toBe($stub);

        });

        it("returns `false` if patcher are not objects", function() {

            expect($this->patchers->add('my_patcher', "not an object"))->toBe(false);

        });

    });

    describe("->get()", function() {

        it("returns `null` for an unexisting patcher", function() {

            $actual = $this->patchers->get('my_patcher');
            expect($actual)->toBe(null);

        });

    });

    describe("->exists()", function() {

        it("returns `true` for an existing patcher", function() {

            $stub = Stub::create();
            $this->patchers->add('my_patcher', $stub);

            $actual = $this->patchers->exists('my_patcher');
            expect($actual)->toBe(true);

        });

        it("returns `false` for an unexisting patcher", function() {

            $actual = $this->patchers->exists('my_patcher');
            expect($actual)->toBe(false);

        });

    });

    describe("->remove()", function() {

        it("removes a patcher", function() {

            $stub = Stub::create();
            $this->patchers->add('my_patcher', $stub);

            $actual = $this->patchers->exists('my_patcher');
            expect($actual)->toBe(true);

            $this->patchers->remove('my_patcher');

            $actual = $this->patchers->exists('my_patcher');
            expect($actual)->toBe(false);

        });

    });

    describe("->clear()", function() {

        it("clears all patchers", function() {

            $stub = Stub::create();
            $this->patchers->add('my_patcher', $stub);

            $actual = $this->patchers->exists('my_patcher');
            expect($actual)->toBe(true);

            $this->patchers->clear();

            $actual = $this->patchers->exists('my_patcher');
            expect($actual)->toBe(false);

        });

    });

    describe("->patchable()", function() {

        it("runs `true` when at least one patcher consider a class as patchable", function() {

            $stub1 = Stub::create();
            Stub::on($stub1)->method('patchable')->andReturn(false);
            $this->patchers->add('patcher1', $stub1);

            $stub2 = Stub::create();
            $this->patchers->add('patcher2', $stub2);
            Stub::on($stub2)->method('patchable')->andReturn(true);

            expect($stub1)->toReceive('patchable')->with('ClassName');
            expect($stub2)->toReceive('patchable')->with('ClassName');

            expect($this->patchers->patchable('ClassName'))->toBe(true);

        });

        it("runs `false` when at no patcher consider a class as patchable", function() {

            $stub1 = Stub::create();
            Stub::on($stub1)->method('patchable')->andReturn(false);
            $this->patchers->add('patcher1', $stub1);

            $stub2 = Stub::create();
            $this->patchers->add('patcher2', $stub2);
            Stub::on($stub2)->method('patchable')->andReturn(false);

            expect($stub1)->toReceive('patchable')->with('ClassName');
            expect($stub2)->toReceive('patchable')->with('ClassName');

            expect($this->patchers->patchable('ClassName'))->toBe(false);

        });

    });

    describe("->process()", function() {

        it("runs a method on all patchers", function() {

            $stub1 = Stub::create();
            $this->patchers->add('patcher1', $stub1);

            $stub2 = Stub::create();
            $this->patchers->add('patcher2', $stub2);

            $path = 'tmp/hello_world.php';
            $code = "<?php\necho 'Hello World!';\n";

            $matcher = function($actual) use ($code) {
                return $code === (string) $actual;
            };

            expect($stub1)->toReceive('process')->with(Arg::toMatch($matcher), $path);
            expect($stub2)->toReceive('process')->with(Arg::toMatch($matcher), $path);

            $this->patchers->process($code, $path);

        });

        it("bails out if code to process is an empty string", function() {

            expect($this->patchers->process(''))->toBe('');

        });

    });


    describe("->findFile()", function() {

        beforeEach(function() {
            $this->loader = Stub::create();
            $this->class = Stub::classname();
            $this->file = 'some/path/file.php';

            $this->stub1 = Stub::create();
            $this->patchers->add('patcher1', $this->stub1);

            $this->stub2 = Stub::create();
            $this->patchers->add('patcher2', $this->stub2);

            $file = $this->file;

            Stub::on($this->stub1)->method('findFile', function() use ($file) {
                return $file;
            });

            Stub::on($this->stub2)->method('findFile', function() use ($file) {
                return $file;
            });
        });

        it("runs findFile() on all patchers", function() {

            expect($this->stub1)->toReceive('findFile')->with($this->loader, $this->class, $this->file);
            expect($this->stub2)->toReceive('findFile')->with($this->loader, $this->class, $this->file);

            $actual = $this->patchers->findFile($this->loader, $this->class, $this->file);
            expect($actual)->toBe('some/path/file.php');

        });

        it("returns patchers overriding if available", function() {

            $path = 'new/path/file.php';

            Stub::on($this->stub2)->method('findFile', function() use ($path) {
                return $path;
            });

            $actual = $this->patchers->findFile($this->loader, $this->class, $this->file);
            expect($actual)->toBe($path);

        });

    });

});