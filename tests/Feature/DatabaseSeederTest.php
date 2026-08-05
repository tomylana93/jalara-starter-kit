<?php

use App\Models\User;

use function Pest\Laravel\artisan;

it('runs the database seeder without creating data', function () {
    pendingCommand(artisan('db:seed', ['--no-interaction' => true]))->assertSuccessful();

    expect(User::query()->count())->toBe(0);
});
