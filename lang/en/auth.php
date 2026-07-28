<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used during authentication for various
    | messages that need to be displayed to the user. These language lines
    | may be modified according to the application's requirements.
    |
    */

    'failed' => 'The provided credentials are incorrect.',
    'password' => 'The provided password is incorrect.',
    'throttle' => 'Too many login attempts. Please try again in :seconds seconds.',

    'session' => [
        'button' => [
            'logout' => 'Log out',
        ],
    ],

    'login' => [
        'title' => 'Log in',
        'description' => 'Enter an email address and password to log in',
        'label' => [
            'email' => 'Email address',
            'password' => 'Password',
            'remember' => 'Stay logged in',
        ],
        'placeholder' => [
            'email' => 'email@example.com',
            'password' => 'Password',
        ],
        'button' => [
            'submit' => 'Log in',
        ],
        'link' => [
            'forgot_password' => 'Forgot password?',
        ],
    ],

    'forgot_password' => [
        'title' => 'Forgot password',
        'description' => 'Enter an email address to receive a password reset link',
        'label' => [
            'email' => 'Email address',
        ],
        'placeholder' => [
            'email' => 'email@example.com',
        ],
        'button' => [
            'submit' => 'Email password reset link',
        ],
        'link' => [
            'return' => 'Or, return to',
            'login' => 'login',
        ],
    ],

    'reset_password' => [
        'title' => 'Reset password',
        'description' => 'Enter a new password below',
        'label' => [
            'email' => 'Email',
            'password' => 'Password',
            'password_confirmation' => 'Confirm password',
        ],
        'placeholder' => [
            'password' => 'Password',
            'password_confirmation' => 'Confirm password',
        ],
        'button' => [
            'submit' => 'Reset password',
        ],
    ],

    'confirm_password' => [
        'title' => 'Confirm password',
        'description' => 'This is a secure area. Confirm the password before continuing.',
        'label' => [
            'password' => 'Password',
        ],
        'button' => [
            'submit' => 'Confirm password',
        ],
    ],

    'verify_email' => [
        'title' => 'Verify email',
        'description' => 'Open the verification link sent by email before continuing.',
        'message' => [
            'instructions' => 'Check the inbox and open the verification link. If it did not arrive, request another email.',
            'sent' => 'A new verification link has been sent by email.',
        ],
        'button' => [
            'resend' => 'Resend verification email',
        ],
    ],

];
