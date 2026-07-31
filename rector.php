<?php

declare(strict_types=1);

use Pest\Rector\Rules\Pest2ToPest3\TapToDeferRector;
use Pest\Rector\Rules\Pest2ToPest3\ToHaveMethodOnClassRector;
use Pest\Rector\Rules\Pest2ToPest3\UsesToExtendRector;
use Pest\Rector\Rules\SimplifyToLiteralBooleanRector;
use Pest\Rector\Rules\ToBeTrueNotFalseRector;
use Pest\Rector\Set\PestSetList;
use Rector\CodingStyle\Rector\ClassMethod\MakeInheritedMethodVisibilitySameAsParentRector;
use Rector\CodingStyle\Rector\Encapsed\EncapsedStringsToSprintfRector;
use Rector\CodingStyle\Rector\Use_\SeparateMultiUseImportsRector;
use Rector\Config\RectorConfig;
use Rector\Php74\Rector\Closure\ClosureToArrowFunctionRector;
use Rector\Php81\Rector\FuncCall\NullToStrictStringFuncCallArgRector;
use Rector\Php83\Rector\ClassMethod\AddOverrideAttributeToOverriddenMethodsRector;
use Rector\Php85\Rector\Property\AddOverrideAttributeToOverriddenPropertiesRector;
use Rector\Renaming\Rector\ClassConstFetch\RenameClassConstFetchRector;
use Rector\TypeDeclaration\Rector\ArrowFunction\AddArrowFunctionReturnTypeRector;
use Rector\TypeDeclaration\Rector\Closure\AddClosureVoidReturnTypeWhereNoReturnRector;
use Rector\TypeDeclaration\Rector\Closure\ClosureReturnTypeRector;
use Rector\TypeDeclaration\Rector\Function_\AddFunctionVoidReturnTypeWhereNoReturnRector;
use Rector\TypeDeclaration\Rector\StmtsAwareInterface\SafeDeclareStrictTypesRector;
use RectorLaravel\Rector\FuncCall\AppToResolveRector;
use RectorLaravel\Set\LaravelSetList;
use RectorLaravel\Set\LaravelSetProvider;

return RectorConfig::configure()
    ->withSetProviders(LaravelSetProvider::class)
    ->withSets([
        LaravelSetList::LARAVEL_ARRAYACCESS_TO_METHOD_CALL,
        LaravelSetList::LARAVEL_ARRAY_STR_FUNCTION_TO_STATIC_CALL,
        LaravelSetList::LARAVEL_CODE_QUALITY,
        LaravelSetList::LARAVEL_COLLECTION,
        LaravelSetList::LARAVEL_CONTAINER_STRING_TO_FULLY_QUALIFIED_NAME,
        LaravelSetList::LARAVEL_ELOQUENT_MAGIC_METHOD_TO_QUERY_BUILDER,
        LaravelSetList::LARAVEL_FACADE_ALIASES_TO_FULL_NAMES,
        LaravelSetList::LARAVEL_FACTORIES,
        LaravelSetList::LARAVEL_IF_HELPERS,
        PestSetList::CODING_STYLE,
    ])
    ->withImportNames(
        removeUnusedImports: true,
    )
    ->withComposerBased(laravel: true)
    ->withPaths([
        __DIR__.'/app',
        __DIR__.'/database/factories',
        __DIR__.'/database/migrations',
        __DIR__.'/database/seeders',
        __DIR__.'/tests',
    ])
    ->withSkip([
        AddOverrideAttributeToOverriddenMethodsRector::class,
        AddOverrideAttributeToOverriddenPropertiesRector::class,
        MakeInheritedMethodVisibilitySameAsParentRector::class,
        RenameClassConstFetchRector::class,
        SafeDeclareStrictTypesRector::class,
        ClosureToArrowFunctionRector::class,
        EncapsedStringsToSprintfRector::class,
        SeparateMultiUseImportsRector::class,
        NullToStrictStringFuncCallArgRector::class,
        AppToResolveRector::class,
        AddClosureVoidReturnTypeWhereNoReturnRector::class,
        ClosureReturnTypeRector::class,
        AddArrowFunctionReturnTypeRector::class,
        AddFunctionVoidReturnTypeWhereNoReturnRector::class,
        /* The suite is already on Pest 5, so the Pest 2 to 3 migration rules only churn it. */
        TapToDeferRector::class,
        ToHaveMethodOnClassRector::class,
        UsesToExtendRector::class,
        /* Rewrites `not->toBeFalse()` on `int|false` values into an assertion that cannot hold. */
        ToBeTrueNotFalseRector::class,
        /* Relaxes an exact `toBe([])` into `toBeEmpty()`, which also accepts null and ''. */
        SimplifyToLiteralBooleanRector::class,
    ])
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        typeDeclarations: true,
        privatization: true,
        earlyReturn: true,
        codingStyle: true,
    )
    ->withPhpSets();
