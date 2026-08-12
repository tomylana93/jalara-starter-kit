<?php

use App\Support\Users\UserImportSheet;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

uses(TestCase::class);

it('logs why a sheet could not be read', function () {
    Log::shouldReceive('warning')
        ->once()
        ->withArgs(fn (string $message, array $context): bool => $message === 'Failed to read a user import sheet.'
            && is_string($context['exception'] ?? null)
            && $context['exception'] !== '');

    $path = tempnam(sys_get_temp_dir(), 'sheet').'.xlsx';
    file_put_contents($path, 'not a workbook');

    try {
        expect(UserImportSheet::tryRead($path))->toBeNull();
    } finally {
        unlink($path);
    }
});
