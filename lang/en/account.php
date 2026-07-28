<?php

return [

    'layout' => [
        'title' => 'Account',
        'description' => 'Manage account, security, and preferences',
        'label' => [
            'profile' => 'Profile',
            'security' => 'Security',
            'appearance' => 'Appearance',
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
        'delete' => [
            'title' => 'Delete account',
            'description' => 'Delete the account and all associated resources',
            'warning' => 'This action cannot be undone.',
            'confirmation_title' => 'Permanently delete this account?',
            'confirmation_description' => 'All associated resources and data will be permanently deleted. Enter the password to confirm.',
            'label' => [
                'password' => 'Password',
                'warning' => 'Warning',
            ],
            'placeholder' => [
                'password' => 'Password',
            ],
            'button' => [
                'cancel' => 'Cancel',
                'delete' => 'Delete account',
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
        'title' => 'Appearance',
        'description' => 'Update the appearance settings for the account',
        'label' => [
            'light' => 'Light',
            'dark' => 'Dark',
            'system' => 'System',
        ],
    ],

];
