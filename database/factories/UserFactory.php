<?php

namespace Database\Factories;

use App\Enums\UserStatus;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'phone' => null,
            'avatar_path' => null,
            'password' => static::$password ??= Hash::make('password'),
            'last_login_at' => null,
            'suspended_until' => null,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function disabled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => UserStatus::Disabled,
        ]);
    }

    public function suspended(?DateTimeInterface $until = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => UserStatus::Suspended,
            'suspended_until' => $until,
        ]);
    }

    public function mustChangePassword(): static
    {
        return $this->state(fn (array $attributes): array => [
            'must_change_password' => true,
        ]);
    }
}
