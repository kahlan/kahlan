<?php
require __DIR__ . '/src/Jit/ClassLoader.php';

use Kahlan\Jit\ClassLoader;

return function () {
    $pwd = realpath(getcwd());
    $vendorName = 'vendor';

    if (!$absolute = realpath(__DIR__ . '/../../autoload.php')) {
        $absolute = realpath(__DIR__ . '/vendor/autoload.php');
    }

    if ($absolute) {
        $autoloader = require $absolute;
    }

    if ($pwd === realpath(__DIR__)) {
        return $autoloader;
    }

    if (file_exists($composerPath = "{$pwd}/composer.json")) {
        $composerJson = json_decode(file_get_contents($composerPath), true);
        $vendorName = isset($composerJson['vendor-dir']) ? $composerJson['vendor-dir'] : $vendorName;
    }

    if (!file_exists("{$pwd}/{$vendorName}/autoload.php")) {
        echo "\033[1;31mYou need to set up the project dependencies using the following commands: \033[0m" . PHP_EOL;
        echo 'curl -s http://getcomposer.org/installer | php' . PHP_EOL;
        echo 'php composer.phar install' . PHP_EOL;
        exit(1);
    }

    $loader = new ClassLoader();

    $map = require "{$pwd}/{$vendorName}/composer/autoload_namespaces.php";
    foreach ($map as $namespace => $path) {
        $loader->set($namespace, $path);
    }

    $map = require "{$pwd}/{$vendorName}/composer/autoload_psr4.php";
    foreach ($map as $namespace => $path) {
        $loader->setPsr4($namespace, $path);
    }

    $classMap = require "{$pwd}/{$vendorName}/composer/autoload_classmap.php";
    if ($classMap) {
        $loader->addClassMap($classMap);
    }

    $loader->register(true);
    if (file_exists("{$pwd}/{$vendorName}/composer/autoload_files.php")) {
        $loader->files(require "{$pwd}/{$vendorName}/composer/autoload_files.php");
    }
    return $loader;
};
