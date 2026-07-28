<?php

namespace App\Actions\Auth;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Auth\Events\Login;

class RecordSuccessfulLogin
{
    public function handle(Login $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        $event->user->forceFill([
            'status' => UserStatus::Active,
            'last_login_at' => now(),
            'failed_login_attempts' => 0,
            'suspended_until' => null,
        ])->save();
    }
}
