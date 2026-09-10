<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\I18n\BuildTranslationBundles;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

#[Signature('i18n:bundle')]
#[Description('Generate frontend translation bundles from Laravel language files')]
class I18nBundleCommand extends Command
{
    public function handle(BuildTranslationBundles $bundles): int
    {
        $files = $bundles->files();

        File::ensureDirectoryExists(resource_path('js/i18n'));
        File::ensureDirectoryExists(resource_path('js/types'));

        foreach ($files as $path => $contents) {
            File::put($path, $contents);
        }

        $this->components->info('Frontend translation bundles generated.');

        return self::SUCCESS;
    }
}
