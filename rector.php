<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Transform\Rector\ClassMethod\ReturnTypeWillChangeRector;

return RectorConfig::configure()
    ->withPaths([
//        __DIR__ . '/assets',
//        __DIR__ . '/automation',
//        __DIR__ . '/languages',
//        __DIR__ . '/lets-encrypt',
//        __DIR__ . '/lib',
//        __DIR__ . '/mailer',
//        __DIR__ . '/modal',
//        __DIR__ . '/onboarding',
//        __DIR__ . '/placeholders',
        __DIR__ . '/pro/security/wordpress/two-fa',
//        __DIR__ . '/progress',
        __DIR__ . '/security/wordpress/two-fa',
//        __DIR__ . '/settings',
//        __DIR__ . '/tests',
//        __DIR__ . '/upgrade',
    ])
    // uncomment to reach your current PHP version
    // ->withPhpSets()
    ->withTypeCoverageLevel(0)
    ->withImportNames(true)
 //   ->withPhp74Sets()
    ->withDeadCodeLevel(1)
    ->withCodeQualityLevel(1)
    ->withRules([
        ReturnTypeWillChangeRector::class,
        \Rector\DeadCode\Rector\ClassMethod\RemoveUselessParamTagRector::class,

        ])
    ;
