<?php

use App\Actions\Account\UpdatePassword;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('stores the new password as a hash', function () {
    $user = User::factory()->create([
        'password' => 'old-password',
    ]);

    (new UpdatePassword)->handle($user, 'new-password');

    expect($user->password)->not->toBe('new-password');
    expect(Hash::check('new-password', $user->password))->toBeTrue();
});
