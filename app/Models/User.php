<?php

namespace App\Models;

use App\Enums\Role;
use App\Enums\UserStatus;
use App\Jobs\SendEmailVerification;
use Carbon\CarbonInterface;
use Database\Factories\UserFactory;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property string $id
 * @property string $name
 * @property string $email
 * @property CarbonInterface|null $email_verified_at
 * @property string|null $phone
 * @property string|null $avatar_path
 * @property-read string|null $avatar
 * @property UserStatus $status
 * @property bool $is_system
 * @property string $password
 * @property bool $must_change_password
 * @property CarbonInterface|null $last_login_at
 * @property int $failed_login_attempts
 * @property CarbonInterface|null $suspended_until
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property CarbonInterface|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 */
#[Appends(['avatar'])]
#[Fillable(['name', 'email', 'password'])]
#[Hidden([
    'password',
    'phone',
    'avatar_path',
    'status',
    'must_change_password',
    'last_login_at',
    'failed_login_attempts',
    'suspended_until',
    'two_factor_secret',
    'two_factor_recovery_codes',
    'remember_token',
])]
class User extends Authenticatable implements MustVerifyEmailContract
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, HasUuids, MustVerifyEmail, Notifiable;

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => UserStatus::Active->value,
        'is_system' => false,
        'must_change_password' => false,
        'failed_login_attempts' => 0,
    ];

    public function sendEmailVerificationNotification(): void
    {
        dispatch(new SendEmailVerification($this));
    }

    /**
     * The public URL of the avatar, or null when none is stored.
     *
     * The underlying storage path stays hidden so only the URL crosses the
     * boundary to the client.
     *
     * @return Attribute<string|null, never>
     */
    protected function avatar(): Attribute
    {
        return Attribute::make(get: function (): ?string {
            if ($this->avatar_path === null || $this->avatar_path === '') {
                return null;
            }

            return Storage::disk('public')->url($this->avatar_path);
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'status' => UserStatus::class,
            'is_system' => 'boolean',
            'password' => 'hashed',
            'must_change_password' => 'boolean',
            'last_login_at' => 'datetime',
            'failed_login_attempts' => 'integer',
            'suspended_until' => 'datetime',
        ];
    }

    /**
     * Get the user's primary role in priority order.
     */
    public function primaryRole(): ?Role
    {
        $roleNames = $this->roles->pluck('name')->all();

        foreach (Role::cases() as $role) {
            if (in_array($role->value, $roleNames, true)) {
                return $role;
            }
        }

        return null;
    }
}
