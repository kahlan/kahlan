<?php

declare(strict_types=1);

use Boundwize\StructArmed\Architecture;
use Boundwize\StructArmed\Preset\Preset;
use Boundwize\StructArmed\Preset\Presets\CodeQualityPreset;

return Architecture::define()
    ->withPresets(Preset::PSR4(), Preset::CODEQUALITY())
    ->layer('Source', 'src')
    ->skip([
        CodeQualityPreset::ANONYMOUS_FUNCTIONS_MUST_BE_STATIC => [
            __DIR__ . '/src/Cli/Kahlan.php',
        ],
    ])
    ->layer('Exception', [
        'src/MissingImplementationException.php',
        'src/PhpErrorException.php',
        'src/QuitException.php',
        'src/SkipException.php',
    ])
    ->layer('Analysis', 'src/Analysis')
    ->layer('Box', 'src/Box')
    ->layer('Code', 'src/Code')
    ->layer('Dir', 'src/Dir')
    ->layer('Filter', 'src/Filter')
    ->layer('Util', 'src/Util')
    ->layer('Cli', 'src/Cli', 'src/Cli/Kahlan.php')
    ->layer('Jit', 'src/Jit', 'src/Jit/Patcher/Layer.php')
    ->layer('Core', [
        'src/Arg.php',
        'src/Block.php',
        'src/Block',
        'src/Expectation.php',
        'src/Given.php',
        'src/Log.php',
        'src/Matcher.php',
        'src/Scope.php',
        'src/Scope',
        'src/Suite.php',
        'src/Summary.php',
    ])
    ->layer('Plugin', 'src/Plugin')
    ->layer('LayerPatcher', 'src/Jit/Patcher/Layer.php')
    ->layer('Matcher', 'src/Matcher')
    ->layer('Dsl', [
        'src/Allow.php',
        'src/functions.php',
    ])
    ->layer('Coverage', 'src/Reporter/Coverage')
    ->layer('Reporter', [
        'src/Reporter',
        'src/Reporters.php',
    ], 'src/Reporter/Coverage')
    ->layer('Console', 'src/Cli/Kahlan.php')
    ->ruleset([
        'Exception'    => [],
        'Analysis'     => [],
        'Box'          => [],
        'Code'         => [],
        'Dir'          => [],
        'Filter'       => [],
        'Util'         => [],
        'Cli'          => [],
        'Jit'          => [],
        'Core'         => ['Analysis', 'Code', 'Exception', 'Filter', 'Util'],
        'Plugin'       => ['Analysis', 'Core', 'Exception', 'Jit'],
        'LayerPatcher' => ['+Plugin'],
        'Matcher'      => ['+Plugin'],
        'Dsl'          => ['Box', '+Plugin'],
        'Coverage'     => ['Dir', 'Jit'],
        'Reporter'     => ['Analysis', 'Cli', 'Core', 'Util', '+Coverage'],
        'Console'      => ['Filter', '+Dsl', '+Matcher', '+Reporter'],
    ]);
