<?php

namespace App\Actions\Fortify;

use App\Enums\Permission;
use App\Enums\UserStatus;
use App\Models\User;
use App\Settings\SecuritySettings;
use App\Settings\SettingsResolver;
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

        $user->reactivateExpiredSuspension();

        if ($user->status !== UserStatus::Active) {
            $request->attributes->set(self::REQUEST_ATTRIBUTE, false);

            throw ValidationException::withMessages([
                'email' => [$user->status->message()],
            ]);
        }

        if ($this->maintenanceDeniesAccess($user)) {
            $request->attributes->set(self::REQUEST_ATTRIBUTE, false);

            throw ValidationException::withMessages([
                'email' => [__('maintenance.message')],
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

    /**
     * Determine whether maintenance denies this account a new session.
     *
     * The predicate matches `EnforceMaintenanceMode`: `manage settings` is the
     * only permission that survives maintenance, and `AuthorizationCatalog`
     * grants it to Super Admin alone. Rejecting here rather than in middleware
     * is deliberate — middleware sees an anonymous request while credentials
     * are being checked, so without this an ordinary account would have its
     * credentials accepted and a session created, only to be answered 503 on
     * the very next request.
     */
    private function maintenanceDeniesAccess(User $user): bool
    {
        $maintenanceEnabled = SettingsResolver::tryResolve(SecuritySettings::class)->maintenanceEnabled ?? false;

        return $maintenanceEnabled && ! $user->can(Permission::ManageSettings->value);
    }
}
