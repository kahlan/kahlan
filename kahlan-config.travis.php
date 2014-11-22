<?php
use filter\Filter;
use kahlan\reporter\Coverage;
use kahlan\reporter\coverage\driver\HHVM;
use kahlan\reporter\coverage\driver\Xdebug;
use kahlan\reporter\coverage\exporter\Coveralls;

$args = $this->args();
$args->argument('coverage', 'default', 3);
$args->argument('scrutinizer', 'default', 'scrutinizer.xml');
$args->argument('coveralls', 'default', 'coveralls.json');

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

Filter::register('kahlan.coveralls', function($chain) {
    $reporter = $this->reporters()->get('coverage');
    if (!$reporter || !$this->args()->exists('coveralls')) {
        return $chain->next();
    }
    Coveralls::write([
        'collector' => $reporter,
        'file' => $this->args()->get('coveralls'),
        'service_name' => 'travis-ci',
        'service_job_id' => getenv('TRAVIS_JOB_ID') ?: null
    ]);
    return $chain->next();
});

Filter::apply($this, 'reporting', 'kahlan.coveralls');


Filter::register('kahlan.quit', function($chain, $success) {

    if (!defined('HHVM_VERSION') && $success) {
        `wget https://scrutinizer-ci.com/ocular.phar`;
        `php ocular.phar code-coverage:upload --format=php-clover "scrutinizer.xml"`;
        `curl -F "json_file=@coveralls.json" https://coveralls.io/api/v1/jobs`;
    }
    return $chain->next();

});

Filter::apply($this, 'quit', 'kahlan.quit');
?>