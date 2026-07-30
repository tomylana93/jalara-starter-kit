<?php

use App\Enums\UserStatus;

return [
    'name' => env('SUPER_ADMIN_NAME', 'Super Admin'),
    'email' => env('SUPER_ADMIN_EMAIL'),
    'phone' => env('SUPER_ADMIN_PHONE'),
    'status' => UserStatus::Active->value,
    'email_verified' => true,
    'password' => env('SUPER_ADMIN_PASSWORD'),
];
