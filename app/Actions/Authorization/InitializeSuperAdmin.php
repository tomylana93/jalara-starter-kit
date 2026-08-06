<?php

namespace App\Actions\Authorization;

use App\Data\Authorization\InitializeSuperAdminData;
use App\Enums\Role;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class InitializeSuperAdmin
{
    public function __construct(private SyncAuthorization $syncAuthorization) {}

    public function handle(InitializeSuperAdminData $data, bool $resetPassword): User
    {
        return DB::transaction(function () use ($data, $resetPassword): User {
            $systemUsers = User::query()->where('is_system', true)->lockForUpdate()->get();

            if ($systemUsers->count() > 1) {
                throw ValidationException::withMessages(['email' => 'More than one system user exists.']);
            }

            $systemUser = $systemUsers->first();
            $emailOwner = User::query()->where('email', $data->email)->lockForUpdate()->first();

            if ($emailOwner && (! $systemUser || ! $emailOwner->is($systemUser))) {
                throw ValidationException::withMessages(['email' => 'The configured email belongs to a regular user.']);
            }

            if ((! $systemUser || $resetPassword) && ! $data->password) {
                throw ValidationException::withMessages(['password' => 'SUPER_ADMIN_PASSWORD is required.']);
            }

            $this->syncAuthorization->handle();
            $systemUser ??= new User;
            $systemUser->forceFill([
                'name' => $data->name,
                'email' => $data->email,
                'phone' => $data->phone,
                'status' => UserStatus::Active,
                'is_system' => true,
                'email_verified_at' => $data->emailVerified ? now() : null,
                'failed_login_attempts' => 0,
                'suspended_until' => null,
            ]);

            if (! $systemUser->exists || $resetPassword) {
                $systemUser->password = $data->password;
            }

            $systemUser->save();
            $systemUser->syncRoles([Role::SuperAdmin->value]);

            return $systemUser->refresh();
        });
    }
}
