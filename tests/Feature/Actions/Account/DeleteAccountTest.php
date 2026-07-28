<?php

use App\Actions\Account\DeleteAccount;
use App\Models\User;

it('deletes the user from the database', function () {
    $user = User::factory()->create();

    (new DeleteAccount)->handle($user);

    expect(User::query()->find($user->id))->toBeNull();
});
