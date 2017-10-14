<?php
require __DIR__ . '/src/Jit/ClassLoader.php';

use Kahlan\Jit\ClassLoader;

return function ($basePath) {

    $import = function($loader, $basePath) {
        if (!file_exists("{$basePath}/autoload.php")) {
            return [];
        }

        $map = require "{$basePath}/composer/autoload_namespaces.php";
        foreach ($map as $namespace => $path) {
            $loader->set($namespace, $path);
        }

        $map = require "{$basePath}/composer/autoload_psr4.php";
        foreach ($map as $namespace => $path) {
            $loader->setPsr4($namespace, $path);
        }

        $classMap = require "{$basePath}/composer/autoload_classmap.php";
        if ($classMap) {
            $loader->addClassMap($classMap);
        }

        if (file_exists("{$basePath}/composer/autoload_files.php")) {
           return require "{$basePath}/composer/autoload_files.php";
        }
        return [];
    };

    $loader = new ClassLoader();
    $files = [];

    if (!$absolute = realpath(__DIR__ . '/../../autoload.php')) {
        $absolute = realpath(__DIR__ . '/vendor/autoload.php');
    }

    if ($absolute) {
        $files += $import($loader, dirname($absolute));
    }

    if (realpath(getcwd()) !== realpath(__DIR__)) {
        $files += $import($loader, $basePath);
    }

    $loader->register(true);
    $loader->files($files);

    return $loader;
};
