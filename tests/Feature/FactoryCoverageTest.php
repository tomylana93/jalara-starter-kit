<?php

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

/*
 * `Model::shouldBeStrict()` includes `preventAccessingMissingAttributes`, so a
 * column a factory never sets is absent from the in-memory model rather than
 * null. Reading it throws — and an `#[Appends]` accessor turns that into a 500
 * during serialization, far from the factory that caused it.
 *
 * Feature tests cannot cover this: they only fail for columns some assertion
 * happens to touch. This walks the schema instead, so a column added to a
 * migration without a matching factory key fails here rather than in a
 * consumer's browser.
 */

/**
 * Every factory class shipped under `database/factories`.
 *
 * @return list<class-string<Factory<Model>>>
 */
function factoryClasses(): array
{
    /*
     * Pest resolves datasets before the application boots, so the path cannot
     * come from `database_path()`.
     */
    $root = dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'factories';

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root)
    );

    $classes = [];

    foreach ($files as $file) {
        if (! $file instanceof SplFileInfo) {
            continue;
        }

        if ($file->getExtension() !== 'php') {
            continue;
        }

        $relative = str_replace(
            [$root.DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, '.php'],
            ['', '\\', ''],
            $file->getPathname(),
        );

        $class = 'Database\\Factories\\'.$relative;
        if (! class_exists($class)) {
            continue;
        }

        if (! is_subclass_of($class, Factory::class)) {
            continue;
        }

        $classes[] = $class;
    }

    sort($classes);

    return $classes;
}

/**
 * Columns a factory does not have to name.
 *
 * The primary key comes from `HasUuids`, timestamps from Eloquent, and anything
 * in the model's `$attributes` is already hydrated on a fresh instance.
 *
 * @return list<string>
 */
function exemptColumns(Model $model): array
{
    return array_merge(
        ['id', 'created_at', 'updated_at', 'deleted_at'],
        array_keys($model->getAttributes()),
    );
}

it('finds the factories it is meant to check', function (): void {
    expect(factoryClasses())->not->toBeEmpty();
});

it('covers every column of its table', function (string $factoryClass): void {
    $factory = new $factoryClass;

    /*
     * `factoryClasses()` already filtered on this, but the dataset hands the
     * class over as a plain string, so the guarantee has to be restated here.
     */
    throw_unless($factory instanceof Factory, InvalidArgumentException::class, "[{$factoryClass}] is not a model factory.");

    $model = $factory->newModel();
    $table = $model->getTable();

    expect(Schema::hasTable($table))->toBeTrue(
        "The table [{$table}] behind [{$factoryClass}] does not exist.",
    );

    $columns = array_column(Schema::getColumns($table), 'name');

    $uncovered = array_values(array_diff(
        $columns,
        array_keys($factory->definition()),
        exemptColumns($model),
    ));

    expect($uncovered)->toBe([], sprintf(
        '%s must set [%s]. Under strict models an unset column throws on access '
        .'instead of reporting null; name it explicitly, with null when that is '
        .'the intended default.',
        class_basename($factoryClass),
        implode(', ', $uncovered),
    ));
})->with(fn (): array => factoryClasses());
