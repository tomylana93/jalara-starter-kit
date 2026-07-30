<?php

use App\Models\User;

it('runs the database seeder without creating data', function () {
    $this->artisan('db:seed', ['--no-interaction' => true])->assertSuccessful();

    expect(User::query()->count())->toBe(0);
});
