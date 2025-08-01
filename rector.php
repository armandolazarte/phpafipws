<?php

declare(strict_types=1);

use Rector\CodingStyle\Rector\PostInc\PostIncDecToPreIncDecRector;
use Rector\Config\RectorConfig;
use Rector\Naming\Rector\Assign\RenameVariableToMatchMethodCallReturnTypeRector;
use Rector\Naming\Rector\Class_\RenamePropertyToMatchTypeRector;
use Rector\Naming\Rector\ClassMethod\RenameParamToMatchTypeRector;
use Rector\Naming\Rector\ClassMethod\RenameVariableToMatchNewTypeRector;
use Rector\Naming\Rector\Foreach_\RenameForeachValueVariableToMatchExprVariableRector;
use Rector\Php81\Rector\Class_\MyCLabsClassToEnumRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/ejemplos',
        __DIR__.'/src',
        __DIR__.'/tests',
    ])
    ->withSkip([
        RenameVariableToMatchNewTypeRector::class,
        RenameVariableToMatchMethodCallReturnTypeRector::class,
        RenamePropertyToMatchTypeRector::class,
        RenameForeachValueVariableToMatchExprVariableRector::class,
        RenameParamToMatchTypeRector::class,
        PostIncDecToPreIncDecRector::class,
        MyCLabsClassToEnumRector::class,
    ])
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        codingStyle: true,
        typeDeclarations: true,
        privatization: true,
        naming: true,
        instanceOf: false,
        earlyReturn: true,
        strictBooleans: true,
        carbon: false,
        rectorPreset: true,
        phpunitCodeQuality: true,
    )
    ->withPhpSets(php81: true);
