<?php

namespace App\Http\Presenters;

use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Builds the personal access token payload shared with the client.
 *
 * The `token` column holds the hash and never crosses the boundary; the
 * plain-text value exists only in the flash message issued at creation time.
 */
final class ApiTokenPresenter
{
    /**
     * @return array{id: string, name: string, last_used_at: string|null, created_at: string|null}
     */
    public static function present(PersonalAccessToken $token): array
    {
        return [
            'id' => (string) $token->getKey(),
            'name' => $token->name,
            'last_used_at' => $token->last_used_at?->toIso8601String(),
            'created_at' => $token->created_at?->toIso8601String(),
        ];
    }

    /**
     * The user's tokens, newest first.
     *
     * @return list<array{id: string, name: string, last_used_at: string|null, created_at: string|null}>
     */
    public static function forUser(User $user): array
    {
        return array_values(
            $user->tokens()
                ->latest()
                ->get()
                ->map(fn (PersonalAccessToken $token): array => self::present($token))
                ->all(),
        );
    }
}
