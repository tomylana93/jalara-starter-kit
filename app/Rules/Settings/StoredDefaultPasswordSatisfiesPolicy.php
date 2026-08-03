<?php

namespace App\Rules\Settings;

use App\Enums\PasswordPolicy;
use App\Settings\SettingsResolver;
use App\Settings\UserProvisioningSettings;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Translation\PotentiallyTranslatedString;

final class StoredDefaultPasswordSatisfiesPolicy implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=):PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $policy = PasswordPolicy::tryFrom(is_string($value) ? $value : '');

        if (! $policy instanceof PasswordPolicy) {
            return;
        }

        $defaultPassword = SettingsResolver::tryResolve(UserProvisioningSettings::class)?->defaultPassword;

        /* A policy may always change while no default password is configured. */
        if ($defaultPassword === null || $defaultPassword === '') {
            return;
        }

        $satisfied = Validator::make(
            ['password' => $defaultPassword],
            ['password' => ['required', 'string', $policy->rule()]],
        )->passes();

        if (! $satisfied) {
            $fail('setting.user_provisioning.default_password.policy_conflict')->translate();
        }
    }
}
