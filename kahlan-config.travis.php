<?php
use filter\Filter;
use kahlan\reporter\Coverage;
use kahlan\reporter\coverage\driver\Xdebug;
use kahlan\reporter\coverage\exporter\Coveralls;

$args = $this->args();
$args->option('ff', 'default', 1);
$args->option('coverage', 'default', 3);
$args->option('coverage-scrutinizer', 'default', 'scrutinizer.xml');
$args->option('coverage-coveralls', 'default', 'coveralls.json');

Filter::register('kahlan.coverage', function($chain) {
    $reporters = $this->reporters();
    $coverage = new Coverage([
        'verbosity' => $this->args()->get('coverage'),
        'driver'    => new Xdebug(),
        'path'      => $this->args()->get('src'),
        'exclude'   => [
            //Exclude Workflow from code coverage reporting
            'src/cli/Kahlan.php',
            //Exclude coverage classes from code coverage reporting
            'src/reporter/coverage/driver/Xdebug.php',
            'src/reporter/coverage/Collector.php',
            //Exclude text based reporter classes from code coverage reporting
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

Filter::apply($this, 'coverageReporter', 'kahlan.coverage');

Filter::register('kahlan.coveralls_reporting', function($chain) {
    $coverage = $this->reporters()->get('coverage');
    if (!$coverage || !$this->args()->get('coverage-coveralls')) {
        return $chain->next();
    }
    Coveralls::write([
        'coverage' => $coverage,
        'file' => $this->args()->get('coverage-coveralls'),
        'service_name' => 'travis-ci',
        'service_job_id' => getenv('TRAVIS_JOB_ID') ?: null
    ]);
    return $chain->next();
});

Filter::apply($this, 'postProcess', 'kahlan.coveralls_reporting');

?>