# Change Log

## Last changes

## 2.5.6 (2016-08-18)

  * **Bugfix:** Allow passing 'string' as Stub's implements option.

## 2.5.5 (2016-08-13)

  * **Add:** Introduce the KAHLAN_VERSION constant.
  * **Add:** Better support of IDE though autocomplete.
  * **Add:** Implements call counting matcher on purpose.

## 2.5.4 (2016-06-15)

  * **Bugfix:** Fixes naming collison conflicts with global functions.

## 2.5.3 (2016-05-10)

  * **Bugfix:** Fixes a parsing issue when a class extends statement matches a use definition.

## 2.5.2 (2016-05-01)

  * **Bugfix:** Fixes an issue related to a BC-break introduced by a composer optimization https://github.com/composer/composer/commit/fd2f51cea8e5f1ef978cd8f90b87b69dc1778976.

## 2.5.1 (2016-05-01)

  * **Bugfix:** Fixes the release number.

## 2.5.0 (2016-04-27)

  * **Change:** Outputs the total coverage after per file coverage.
  * **Bugfix:** Skips specs when related extentions are not available.
  * **Bugfix:** Fixes return types of non-builtins types in Stubs generation.
  * **BC break:** Exits and display an error message when `--coverage` is used but no driver are available.

## 2.4.1 (2016-03-23)

  * **Bugfix:** Fixes stub generation for non-builtin PHP7 return types.

## 2.4.0 (2016-03-20)

  * **Add:** Adds a JSON reporter.
  * **Add:** Adds a TAP reporter.
  * **Add:** Allows to redirect reporter outputs to a file.
  * **BC break:** the `--reporter` option is now managed as an array.

## 2.3.2 (2016-02-18)

  * **Change:** Internal dependency container function refactoring.

## 2.3.1 (2016-02-13)

  * **Add:** Improves code coverage accuracy for unconsistant XDEBUG/PHPDBG code coverage result.

## 2.3.0 (2016-02-10)

  * **Add:** Supports PHP7 variadic functions.
  * **Add:** Supports PHP7 scalar typehints.

## 2.2.0 (2016-02-10)

  * **Add:** Supports PHP7 return types.
  * **Add:** Supports PHP7 group use declarations.
  * **Bugfix:** Fixes a Layer patcher issue when extends is not an absolute class name.

## 2.1.0 (2016-01-17)

  * **BC break:** Patchers can now be applied lazily.

## 2.0.1 (2015-12-09)

  * **Bugfix:** Fixes a reporting issue related to the new repository structure.

## 2.0.0 (2015-12-05)

  * **BC break:** Uses PascalCase conventions instead of lowercase for all namespaces.

## 1.3.0 (2015-12-05)

  * **Add:** Creates a standalone version.
  * **Add:** Reintroduces PHP 5.4 support.
  * **BC break:** `use filter\Filter` must now be `use kahlan\filter\Filter` in `kahlan-config.php`.

## 1.2.11 (2015-11-24)

  * **Add:** Adds `given()` function to set lazy loadable variables.

## 1.2.10 (2015-11-23)

  * **Add:** Allows Kahlan's binary to deal with custom composer.json `"vendor-dir"` config.

## 1.2.9 (2015-11-23)

  * **Bugfix:** Makes sure Kahlan's global function can't be includes twice.

## 1.2.8 (2015-11-22)

  * **Bugfix:** Fixes a cwd issue when installed globally.

## 1.2.7 (2015-11-07)

  * **Add:** Adds a lcov compatible exporter.
  * **Bugfix:** Fixes a minor issue with the istanbul exporter.

## 1.2.6 (2015-11-07)

  * **Add:** Adds an istanbul compatible exporter.

## 1.2.5 (2015-11-04)

  * **Add:** Restores IDE autocomplete feature for `expect()`.
  * **Bugfix:** Fixes `PointCut` patching with generators.

## 1.2.4 (2015-11-03)

  * **Bugfix:** Bugfix ability to disable Kahlan functions by environment variable.

## 1.2.3 (2015-11-03)

  * **Add:** Added ability to disable Kahlan functions by environment variable.
  * **Bugfix:** Fixes reported backtrace which was not accurate for deferred matchers.

## 1.2.2 (2015-10-22)

  * **Bugfix:** Fixes `ToContainKey` when dealing with plain arrays and `null` values.

## 1.2.1 (2015-10-17)

  * **Bugfix:** Fixes some Windows related issues.

## 1.2.0 (2015-10-13)

  * **Add:** Allows to set contextualized matchers.
  * **Add:** Introduces the `waitsFor` statement.
  * **BC break:** Drops PHP 5.4 support.
  * **BC break:** Internal classes has been refactored/renamed.

## 1.1.9 (2015-09-03)

  * **Bugfix:** escapes file path for coverage metrics.

## 1.1.8 (2015-07-30)

  * **Bugfix:** fixes an issue when stub needs to auto override methods where parameters are passed by reference.

## 1.1.7 (2015-07-27)

  * **Bugfix:** fixes a control structures issue when present in uppercase.

## 1.1.6 (2015-07-27)

  * **Bugfix:** fixes the order of `toContain()` matcher.

## 1.1.5 (2015-06-26)

  * **Add:** adds the `toContainKey()` matcher.
  * **Bugfix:** monkey patching now supports `or`, `and` && `xor` alternative syntax.

## 1.1.4 (2015-06-04)

  * **Bugfix:** makes report backtrace more accurate on exceptions.

## 1.1.3 (2015-03-21)

  * **Add:** remove composer minimum stability requirement.

## 1.1.2 (2015-03-20)

  * **Add:** adds the command line --cc option to clear the cache.
  * **Add:** auto clear cache on "composer update".
  * **Add:** adds the command line --version option.
  * **Add:** adds `toMatchEcho` matcher.
  * **Bugfix:** fixes report duplication of some skip exceptions.
  * **Bugfix:** resets `not` to false after any matcher call.

## 1.1.1 (2015-03-17)

  * **Bugfix:** fixes a double open tag issue with the `Layer` patcher.
  * **Bugfix:** fixes missing pointcut patching in the `Layer` patcher.

## 1.1.0 (2015-02-25)

  * **Add:** allows Stubs to override all public method of their parent class by setting the `'layer'` option to `true`.
  * **Add:** introduces the `Layer` proxy to be able to stub methods inherited from PHP core method.
  * **Change:** the look & feel of reporters has been modified.
  * **Bugfix:** adds a default value for stubbed function parameters only when exists.
  * **Bugfix:** returns absolute namespace for typehint
  * **Bugfix:** generalizes method overriding with stubs.
  * **BC break:** the Stubs `'params'` option now identifies each values to pass to `__construct()`.
  * **BC break:** reporter's hooks has been renamed and now receive a report instance as parameter instead of an array.

## 1.0.6 (2015-02-11)

  * **Add:** implements missing Jasmine `expect(string)->toContain(substring)` behavior.
  * **Change:** Allows arguments to also be set in kahlan config files.
  * **Bugfix:** fixes Monkey patcher when some patchable code are outside namespaces/classes or functions.

## 1.0.5 (2015-02-10)

  * **Bugfix:** resolves default cache path (based on`sys_get_temp_dir()`) to be a real dir on OS X.

## 1.0.4 (2015-02-03)

  * **Deprecate:** deprecates ddescribe/ccontext/iit in flavor of fdescribe/fcontext/fit (Jasmine 2.x naming)

## 1.0.3 (2015-02-02)

  * **Bugfix:** fixes `use` statement patching for partial namespace

## 1.0.2 (2015-02-01)

  * **Change:** the terminal reporter displaying has been modified
  * **Bugfix:** fixes code coverage driver to make it work with HHVM
  * **BC break:** the `'autoloader'` filter entry point has been renamed to `'interceptor'`

## 1.0.1 (2015-01-28)

  * **Add:** new reporter `--reporter=verbose`

## 1.0.0 (2015-01-24)

  * Initial Stable Release
