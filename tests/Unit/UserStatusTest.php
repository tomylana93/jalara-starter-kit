<?php

use App\Enums\UserStatus;
use Tests\TestCase;

uses(TestCase::class);

it('provides translated options', function () {
    expect(UserStatus::options())->toBe([
        ['label' => 'Active', 'value' => 'active'],
        ['label' => 'Disabled', 'value' => 'disabled'],
        ['label' => 'Suspended', 'value' => 'suspended'],
    ]);
});

it('includes additional option fields', function () {
    expect(UserStatus::options(['variant' => 'variant']))->toHaveKey('0.variant', 'default')
        ->and(UserStatus::options(['variant' => 'variant']))->toHaveKey('1.variant', 'destructive')
        ->and(UserStatus::options(['variant' => 'variant']))->toHaveKey('2.variant', 'secondary');
});

it('rejects an invalid additional field method', function () {
    UserStatus::options(['missing' => 'missing']);
})->throws(LogicException::class, 'Method [missing] does not exist on enum [App\Enums\UserStatus].');

it('localizes status labels and messages', function () {
    app()->setLocale('id');

    expect(UserStatus::Disabled->label())->toBe('Dinonaktifkan')
        ->and(UserStatus::Suspended->message())->toBe('Akun ditangguhkan.');
});
