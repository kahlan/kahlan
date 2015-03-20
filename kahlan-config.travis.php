<?php
use filter\Filter;
use kahlan\reporter\Coverage;
use kahlan\reporter\coverage\driver\Xdebug;
use kahlan\reporter\coverage\exporter\Coveralls;
use kahlan\reporter\coverage\exporter\CodeClimate;

$args = $this->args();
$args->argument('coverage', 'default', 3);

Filter::register('kahlan.coverage', function($chain) {
    if (!extension_loaded('xdebug')) {
        return;
    }
    $reporters = $this->reporters();
    $coverage = new Coverage([
        'verbosity' => $this->args()->get('coverage'),
        'driver'    => new Xdebug(),
        'path'      => $this->args()->get('src'),
        'exclude'   => [
            //Exclude init script
            'src/init.php',
            //Exclude Workflow from code coverage reporting
            'src/cli/Kahlan.php',
            //Exclude coverage classes from code coverage reporting (don't know how to test the tester)
            'src/reporter/coverage/driver/Xdebug.php',
            'src/reporter/coverage/Collector.php',
            //Exclude HHVM because of HHVM_VERSION return
            'src/reporter/coverage/driver/HHVM.php',
            //Exclude text based reporter classes from code coverage reporting (a bit useless)
            'src/reporter/Dot.php',
            'src/reporter/Bar.php',
            'src/reporter/Verbose.php',
            'src/reporter/Terminal.php',
            'src/reporter/Reporter.php',
            'src/reporter/Coverage.php',
            'src/reporter/Pretty.php',
        ],
        'colors'    => !$this->args()->get('no-colors')
    ]);
    $reporters->add('coverage', $coverage);
});

Filter::apply($this, 'coverage', 'kahlan.coverage');

Filter::register('kahlan.coverage-exporter', function($chain) {
    $reporter = $this->reporters()->get('coverage');
    if (!$reporter) {
        return;
    }
    Coveralls::write([
        'collector'      => $reporter,
        'file'           => 'coveralls.json',
        'service_name'   => 'travis-ci',
        'service_job_id' => getenv('TRAVIS_JOB_ID') ?: null
    ]);
    CodeClimate::write([
        'collector'  => $reporter,
        'file'       => 'codeclimate.json',
        'branch'     => getenv('TRAVIS_BRANCH') ?: null,
        'repo_token' => 'a4b5637db5629f60a5d3fc1a070b2339479ff8989c6491dfc6a19cada5e4ffaa'
    ]);
    return $chain->next();
});

Filter::apply($this, 'reporting', 'kahlan.coverage-exporter');
