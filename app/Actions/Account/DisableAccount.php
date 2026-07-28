<?php

namespace App\Actions\Account;

use App\Enums\UserStatus;
use App\Models\User;

final class DisableAccount
{
    public function handle(User $user): void
    {
        $user->forceFill([
            'status' => UserStatus::Disabled,
            'failed_login_attempts' => 0,
            'suspended_until' => null,
        ])->save();
    }
}
