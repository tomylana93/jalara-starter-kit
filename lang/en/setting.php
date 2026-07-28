<?php

return [

    'password_policy' => [
        'basic' => 'Basic',
        'standard' => 'Standard',
        'strict' => 'Strict',
    ],

    'branding' => [
        'auth_layout' => [
            'simple' => 'Simple',
            'card' => 'Card',
            'split' => 'Split',
        ],

        'app_layout' => [
            'sidebar' => 'Sidebar',
            'header' => 'Header',
        ],

        'color_theme' => [
            'neutral' => 'Neutral',
            'blue' => 'Blue',
            'emerald' => 'Emerald',
            'violet' => 'Violet',
            'rose' => 'Rose',
            'amber' => 'Amber',
        ],

        'font_preset' => [
            'instrument_sans' => 'Instrument Sans',
            'system_sans' => 'System sans-serif',
            'system_serif' => 'System serif',
            'system_mono' => 'System monospace',
        ],
    ],

    'date_format' => [
        'iso' => 'Year-Month-Day (2026-07-28)',
        'day_month_year_slashed' => 'Day/Month/Year (28/07/2026)',
        'month_day_year_slashed' => 'Month/Day/Year (07/28/2026)',
        'day_short_month_year' => 'Day Month Year (28 Jul 2026)',
    ],

    'locale' => [
        'en' => 'English',
        'id' => 'Indonesian',
    ],

    'user_provisioning' => [
        'default_password' => [
            'policy_conflict' => 'The stored default password for new users does not satisfy the selected policy. Update the default password first.',
            'updated' => 'The default password was updated.',
            'removed' => 'The default password was removed.',
            'not_configured' => 'The default password for new users has not been configured.',
        ],
    ],

    'maintenance' => [
        'message' => 'The application is under maintenance. Please try again later.',
    ],

    'mail' => [
        'test' => [
            'subject' => 'Mail configuration test for :company',
            'heading' => 'Mail configuration test',
            'intro' => 'This message confirms that :company can deliver email with the current mail settings.',
            'sender' => 'Messages are sent as :name (:address).',
            'sent' => 'The test message was sent.',
        ],
    ],

];
