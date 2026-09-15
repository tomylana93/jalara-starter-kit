<?php

namespace App\Console\Commands;

use App\Support\TranslationExporter;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use InvalidArgumentException;

#[Signature('lang:export {--source= : Source directory, relative to the application root} {--output= : Output directory, relative to the application root} {--fallback= : Locale used as the TypeScript schema}')]
#[Description('Export Laravel translations for the frontend')]
class ExportTranslations extends Command
{
    public function handle(TranslationExporter $exporter): int
    {
        $sourcePath = $this->absolutePath($this->option('source') ?: lang_path());
        $outputPath = $this->absolutePath($this->option('output') ?: resource_path('js/locales/generated'));
        $fallbackLocale = $this->option('fallback') ?: config('app.fallback_locale');

        try {
            $result = $exporter->export($sourcePath, $outputPath, $fallbackLocale);
        } catch (InvalidArgumentException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info(sprintf(
            'Exported %d messages for %d locale(s).',
            $result['messages'],
            count($result['locales']),
        ));

        return self::SUCCESS;
    }

    private function absolutePath(string $path): string
    {
        return str_starts_with($path, DIRECTORY_SEPARATOR) ? $path : base_path($path);
    }
}
