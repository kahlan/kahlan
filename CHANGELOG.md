# Change Log

## Last changes

  * Bugfix: generalizes method overriding with stubs.
  * Change: the look & feel of reporters has been modified.
  * BC break: reporter's hooks has been renamed and now receive a report instance as parameter instead of an array.

## 1.0.6 (2015-02-11)

  * Change: Allows arguments to also be set in kahlan config files.
  * Bugfix: fixes Monkey patcher when some patchable code are outside namespaces/classes or functions.
  * Add: implements missing Jasmine `expect(string)->toContain(substring)` behavior.

## 1.0.5 (2015-02-10)

  * Bugfix: resolves default cache path (based on`sys_get_temp_dir()`) to be a real dir on OS X.

## 1.0.4 (2015-02-03)

  * Deprecate: deprecates ddescribe/ccontext/iit in flavor of fdescribe/fcontext/fit (Jasmine 2.x naming)

## 1.0.3 (2015-02-02)

  * Bugfix: fixes `use` statement patching for partial namespace

## 1.0.2 (2015-02-01)

  * Bugfix: fixes code coverage driver to make it work with HHVM
  * BC break: the `'autoloader'` filter entry point has been renamed to `'interceptor'`
  * Change: the terminal reporter displaying has been modified

## 1.0.1 (2015-01-28)

  * Add: new reporter `--reporter=verbose`

## 1.0.0 (2015-01-24)

  * Initial Stable Release
