<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([__DIR__ . '/src'])
    ->withSkip([
        \Rector\PostRector\Rector\UnusedImportRemovingPostRector::class => [
            __DIR__ . '/src/Jit/TokenStream.php'
        ],
    ])
    ->withPhpSets()
    ->withImportNames();
