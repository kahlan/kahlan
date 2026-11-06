<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([__DIR__ . '/src'])
    ->withSkip([
        __DIR__ . '/src/Jit/TokenStream.php',
        \Rector\Php74\Rector\If_\IfToNullCoalescingAssignRector::class,
        \Rector\Php73\Rector\FuncCall\StringifyStrNeedlesRector::class => [
            __DIR__ . '/src/Reporter/Coverage/Collector.php',
        ],
    ])
    ->withPhpSets()
    ->withImportNames();
