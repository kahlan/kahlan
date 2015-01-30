<?php
use filter\Filter;
use kahlan\reporter\Coverage;
use kahlan\reporter\coverage\driver\HHVM;
use kahlan\reporter\coverage\driver\Xdebug;
use kahlan\reporter\coverage\exporter\Coveralls;
use kahlan\reporter\coverage\exporter\CodeClimate;

$args = $this->args();
$args->argument('coverage', 'default', 3);

Filter::register('kahlan.coverage', function($chain) {
    if (defined('HHVM_VERSION')) {
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
            'src/reporter/Terminal.php',
            'src/reporter/Reporter.php',
            'src/reporter/Coverage.php',
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
        'repo_token' => '44d9595530151e99ebc6d2b63f0cea5b30aaaecf86767a2ac6717aa0c2be77f3'
    ]);
    return $chain->next();
});

Filter::apply($this, 'reporting', 'kahlan.coverage-exporter');
?>
