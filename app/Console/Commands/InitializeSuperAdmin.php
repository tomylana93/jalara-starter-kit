<?php

namespace App\Console\Commands;

use App\Actions\Authorization\InitializeSuperAdmin as InitializeSuperAdminAction;
use App\Data\Authorization\InitializeSuperAdminData;
use App\Enums\UserStatus;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('auth:init-superadmin {--reset-password}')]
#[Description('Initialize or restore the protected Super Admin user and enforce its role.')]
class InitializeSuperAdmin extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(InitializeSuperAdminAction $initializeSuperAdmin): int
    {
        $name = config('superadmin.name');
        $email = config('superadmin.email');
        $phone = config('superadmin.phone');
        $status = config('superadmin.status');
        $emailVerified = config('superadmin.email_verified');
        $password = config('superadmin.password');

        if (! is_string($name) || $name === '' || ! is_string($email) || $email === '' || ! is_string($status) || $status === '') {
            $this->components->error('The superadmin.name, superadmin.email, and superadmin.status configuration values are required.');

            return self::FAILURE;
        }

        try {
            $user = $initializeSuperAdmin->handle(new InitializeSuperAdminData(
                name: $name,
                email: $email,
                phone: is_string($phone) ? $phone : null,
                status: UserStatus::from($status),
                emailVerified: (bool) $emailVerified,
                password: is_string($password) && $password !== '' ? $password : null,
            ), (bool) $this->option('reset-password'));
        } catch (Throwable $throwable) {
            $this->components->error($throwable->getMessage());

            return self::FAILURE;
        }

        $this->components->info("Super Admin initialized for [{$user->email}].");

        return self::SUCCESS;
    }
}
