<?php

return [

    'layout' => [
        'title' => 'Account',
        'description' => 'Manage account, security, and preferences',
        'label' => [
            'profile' => 'Profile',
            'security' => 'Security',
        ],
    ],

    'profile' => [
        'title' => 'Profile',
        'description' => 'Update the name and email address',
        'label' => [
            'name' => 'Name',
            'email' => 'Email address',
        ],
        'placeholder' => [
            'name' => 'Full name',
            'email' => 'Email address',
        ],
        'button' => [
            'save' => 'Save',
        ],
        'message' => [
            'updated' => 'Profile updated.',
        ],
        'disable' => [
            'title' => 'Disable account',
            'description' => 'Disable access while retaining account data',
            'warning' => 'The account will be logged out and an administrator is required to reactivate it.',
            'confirmation_title' => 'Disable this account?',
            'confirmation_description' => 'The account and associated data will be retained. Enter the password to confirm logout and disable access.',
            'label' => [
                'password' => 'Password',
                'warning' => 'Warning',
            ],
            'placeholder' => [
                'password' => 'Password',
            ],
            'button' => [
                'cancel' => 'Cancel',
                'disable' => 'Disable account',
            ],
        ],
    ],

    'security' => [
        'title' => 'Update password',
        'description' => 'Use a long, random password to keep the account secure',
        'label' => [
            'current_password' => 'Current password',
            'password' => 'New password',
            'password_confirmation' => 'Confirm password',
        ],
        'placeholder' => [
            'current_password' => 'Current password',
            'password' => 'New password',
            'password_confirmation' => 'Confirm password',
        ],
        'button' => [
            'save' => 'Save',
        ],
        'message' => [
            'updated' => 'Password updated.',
            'must_change_password_title' => 'Password change required',
            'must_change_password' => 'Set a new password before continuing to the application.',
        ],
    ],

    'appearance' => [
        'label' => [
            'light' => 'Light',
            'dark' => 'Dark',
            'system' => 'System',
        ],
        'button' => [
            'toggle' => 'Change appearance',
        ],
    ],

];
