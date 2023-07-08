<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php56\Rector\FunctionLike\AddDefaultValueForUndefinedVariableRector;
use Rector\Php71\Rector\FuncCall\CountOnNullRector;
use Rector\Set\ValueObject\LevelSetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->importNames();
    $rectorConfig->paths([
        __DIR__ . '/src',
    ]);

    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_72
    ]);

    $rectorConfig->skip([
        CountOnNullRector::class,
        AddDefaultValueForUndefinedVariableRector::class => [
            __DIR__ . '/src/Expectation.php',
        ],
    ]);
};
