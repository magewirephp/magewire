<?php

declare(strict_types=1);

use Rector\CodingStyle\Rector\FuncCall\ArraySpreadInsteadOfArrayMergeRector;
use Rector\Config\RectorConfig;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;

return RectorConfig::configure()
    ->withPaths([
        realpath('dist'),
    ])
    ->withRules([
        ClassPropertyAssignToConstructorPromotionRector::class,
        ArraySpreadInsteadOfArrayMergeRector::class
    ])
    ->withPhpSets(
        php82: true
    )
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        codingStyle: true,
        typeDeclarations: true,
    )
    ->withNoDiffs()
    //->withTypeCoverageLevel(Level::PHP_82)
    ->withImportNames(
        removeUnusedImports: true
    );
