## CLI Options

Below are all of Kahlan's option obtained through the `kahlan --help` command line.

```
Configuration Options:

  --config=<file>                     The PHP configuration file to use (default: `'kahlan-config.php'`).
  --src=<path>                        Paths of source directories (default: `['src']`).
  --spec=<path>                       Paths of specification directories (default: `['spec']`).
  --pattern=<pattern>                 A shell wildcard pattern (default: `'*Spec.php'`).

Reporter Options:

  --reporter=<name>[:<output_file>]   The name of the text reporter to use, the built-in text reporters
                                      are `'dot'`, `'bar'`, `'json'`, `'tap'` & `'verbose'` (default: `'dot'`).
                                      You can optionally redirect the reporter output to a file by using the
                                      colon syntax (multiple --reporter options are also supported).

Code Coverage Options:

  --coverage=<integer|string>         Generate code coverage report. The value specify the level of
                                      detail for the code coverage report (0-4). If a namespace, class, or
                                      method definition is provided, it will generate a detailed code
                                      coverage of this specific scope (default `''`).
  --clover=<file>                     Export code coverage report into a Clover XML format.
  --istanbul=<file>                   Export code coverage report into an istanbul compatible JSON format.
  --lcov=<file>                       Export code coverage report into a lcov compatible text format.

Test Execution Options:

  --ff=<integer>                      Fast fail option. `0` mean unlimited (default: `0`).
  --no-colors=<boolean>               To turn off colors. (default: `false`).
  --no-header=<boolean>               To turn off header. (default: `false`).
  --include=<string>                  Paths to include for patching. (default: `['*']`).
  --exclude=<string>                  Paths to exclude from patching. (default: `[]`).
  --persistent=<boolean>              Cache patched files (default: `true`).
  --cc=<boolean>                      Clear cache before spec run. (default: `false`).
  --autoclear                         Classes to autoclear after each spec (default: [
                                          `'Kahlan\Plugin\Monkey'`,
                                          `'Kahlan\Plugin\Call'`,
                                          `'Kahlan\Plugin\Stub'`,
                                          `'Kahlan\Plugin\Quit'`
                                      ])

Miscellaneous Options:

  --help                 Prints this usage information.
  --version              Prints Kahlan version

Note: The `[]` notation in default values mean that the related option can accepts an array of values.
To add additional values, just repeat the same option many times in the command line.
```
