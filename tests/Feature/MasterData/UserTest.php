<?php

use App\Models\MasterData\User;
use Ramsey\Uuid\Uuid;

it('assigns a UUIDv7 primary key when created', function (): void {
    $user = User::factory()->create();

    expect(Uuid::fromString($user->id)->getVersion())->toBe(7);
});
