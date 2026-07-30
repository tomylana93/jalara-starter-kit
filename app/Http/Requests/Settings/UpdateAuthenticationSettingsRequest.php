<?php

namespace App\Http\Requests\Settings;

use App\Enums\PasswordPolicy;
use App\Settings\SettingsResolver;
use App\Settings\UserProvisioningSettings;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UpdateAuthenticationSettingsRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'requireEmailVerification' => ['required', 'boolean'],
            'passwordPolicy' => [
                'required',
                'string',
                Rule::enum(PasswordPolicy::class),
                $this->defaultPasswordSatisfiesPolicy(),
            ],
            'sessionLifetimeMinutes' => ['required', 'integer', 'min:5', 'max:10080'],
        ];
    }

    /**
     * Reject a policy the configured default password would no longer satisfy.
     *
     * The administrator has to update the default password first; the policy is
     * never weakened and an invalid default is never kept.
     *
     * @return Closure(string, mixed, Closure): void
     */
    private function defaultPasswordSatisfiesPolicy(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
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
        };
    }
}
