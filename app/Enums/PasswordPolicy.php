<?php

namespace App\Enums;

use App\Concerns\HasOptions;
use App\Contracts\HasLabel;
use Illuminate\Validation\Rules\Password;

enum PasswordPolicy: string implements HasLabel
{
    use HasOptions;

    case Basic = 'basic';
    case Standard = 'standard';
    case Strict = 'strict';

    /**
     * Get the human-readable label for the password policy.
     */
    public function label(): string
    {
        return match ($this) {
            self::Basic => __('setting.password_policy.basic'),
            self::Standard => __('setting.password_policy.standard'),
            self::Strict => __('setting.password_policy.strict'),
        };
    }

    /**
     * Get the password validation rule enforced by the policy.
     */
    public function rule(): Password
    {
        return match ($this) {
            self::Basic => Password::min(8),
            self::Standard => Password::min(10)->mixedCase()->numbers(),
            self::Strict => Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised(),
        };
    }
}
