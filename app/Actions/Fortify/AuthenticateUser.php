<?php

namespace App\Actions\Fortify;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthenticateUser
{
    private const string REQUEST_ATTRIBUTE = 'authenticated_user_resolution';

    public function handle(Request $request): ?User
    {
        if ($request->attributes->has(self::REQUEST_ATTRIBUTE)) {
            $resolvedUser = $request->attributes->get(self::REQUEST_ATTRIBUTE);

            return $resolvedUser instanceof User ? $resolvedUser : null;
        }

        $user = User::query()->where('email', $request->string('email')->toString())->first();

        if (! $user instanceof User || ! Hash::check($request->string('password')->toString(), $user->password)) {
            $request->attributes->set(self::REQUEST_ATTRIBUTE, false);

            return null;
        }

        $this->reactivateExpiredSuspension($user);

        if ($user->status !== UserStatus::Active) {
            $request->attributes->set(self::REQUEST_ATTRIBUTE, false);

            throw ValidationException::withMessages([
                'email' => [$user->status->message()],
            ]);
        }

        if (config('hashing.rehash_on_login', true) && Hash::needsRehash($user->password)) {
            $user->forceFill([
                'password' => $request->string('password')->toString(),
            ])->save();
        }

        $request->attributes->set(self::REQUEST_ATTRIBUTE, $user);

        return $user;
    }

    private function reactivateExpiredSuspension(User $user): void
    {
        if (
            $user->status === UserStatus::Suspended
            && $user->suspended_until?->isPast()
        ) {
            $user->forceFill([
                'status' => UserStatus::Active,
                'suspended_until' => null,
            ])->save();
        }
    }
}
