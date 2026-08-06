<?php

return [

    'layout' => [
        'title' => 'Settings',
        'description' => 'Manage application-wide configuration',
        'label' => [
            'general' => 'General',
            'authentication' => 'Authentication',
            'user_provisioning' => 'User provisioning',
            'mail' => 'Mail',
            'security' => 'Security',
            'branding' => 'Branding',
            'chat' => 'Chat',
            'backups' => 'Backups',
        ],
    ],

    'backup' => [
        'title' => 'Backups',
        'description' => 'Scheduled database and media archives',
    ],

    'general' => [
        'title' => 'General',
        'description' => 'Application identity, language, and date presentation',
        'label' => [
            'application_name' => 'Application name',
            'description' => 'Description',
            'default_locale' => 'Default language',
            'date_format' => 'Date format',
        ],
        'placeholder' => [
            'application_name' => 'Application name',
            'description' => 'Short description of the application',
        ],
        'help' => [
            'application_name' => 'Used for the document title suffix and runtime identity in email, notifications, and other server-side text.',
            'description' => 'Internal summary of what the application is used for.',
            'default_locale' => 'Language used when no other preference applies.',
            'date_format' => 'Format used to present dates.',
        ],
        'button' => [
            'save' => 'Save',
        ],
        'message' => [
            'updated' => 'General settings updated.',
        ],
    ],

    'authentication' => [
        'title' => 'Authentication',
        'description' => 'Verification, password strength, and session lifetime',
        'label' => [
            'require_email_verification' => 'Require email verification',
            'password_policy' => 'Password policy',
            'session_lifetime_minutes' => 'Session lifetime (minutes)',
        ],
        'help' => [
            'require_email_verification' => 'While enabled, an unverified account cannot reach the application.',
            'password_policy' => 'Applies to every new password, including the default password for new users.',
            'session_lifetime_minutes' => 'An inactive session expires after this many minutes.',
        ],
        'button' => [
            'save' => 'Save',
        ],
        'message' => [
            'updated' => 'Authentication settings updated.',
        ],
    ],

    'password_policy' => [
        'basic' => 'Basic',
        'standard' => 'Standard',
        'strict' => 'Strict',
        'description' => [
            'basic' => 'At least 8 characters.',
            'standard' => 'At least 10 characters, with upper and lower case letters and a number.',
            'strict' => 'At least 12 characters, with mixed case, letters, numbers, symbols, and no password known from a data breach.',
        ],
    ],

    'branding' => [
        'title' => 'Branding',
        'description' => 'Visible identity, layout, color, and typography',
        'label' => [
            'company_name' => 'Company name',
            'footer_text' => 'Footer text',
            'identity_mode_group' => 'Identity mode',
            'logo' => 'Logo',
            'logo_dark' => 'Logo (dark mode)',
            'icon' => 'Icon',
            'icon_dark' => 'Icon (dark mode)',
            'auth_background' => 'Authentication background (split layout)',
            'auth_layout_group' => 'Authentication layout',
            'app_layout_group' => 'Application layout',
            'color_theme_group' => 'Color theme',
            'font_pair_group' => 'Font pair',
        ],
        'placeholder' => [
            'company_name' => 'Company name',
            'footer_text' => 'Text shown at the bottom of every page',
        ],
        'help' => [
            'company_name' => 'The visible company identity used in logo text and branded interface elements. The document title suffix is configured under general settings.',
            'footer_text' => 'Optional line rendered in the footer of authentication and application pages.',
            'identity_mode_group' => 'Whether branded areas show the logo, or the icon next to the application name.',
            'auth_layout_group' => 'Arrangement of the login, registration, and password pages.',
            'app_layout_group' => 'Navigation arrangement inside the application.',
            'color_theme_group' => 'Brand color tokens. Light and dark mode remain a separate preference.',
            'font_pair_group' => 'Heading and body typefaces used across the interface.',
        ],
        'preview' => [
            'font_body' => 'Comfortable body text throughout the application',
        ],
        'button' => [
            'save' => 'Save',
            'upload' => 'Upload',
            'replace' => 'Replace',
            'remove' => 'Remove',
        ],
        'message' => [
            'updated' => 'Branding settings updated.',
            'asset_updated' => 'Branding image updated.',
            'asset_removed' => 'Branding image removed.',
        ],

        'identity_mode' => [
            'logo' => 'Logo',
            'icon_text' => 'Icon and application name',
        ],

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
            'teal' => 'Teal',
            'cyan' => 'Cyan',
            'indigo' => 'Indigo',
            'orange' => 'Orange',
        ],

        'font_pair' => [
            'instrument_sans' => 'Instrument Sans + Instrument Sans',
            'space_grotesk_inter' => 'Space Grotesk + Inter',
            'poppins_inter' => 'Poppins + Inter',
            'montserrat_open_sans' => 'Montserrat + Open Sans',
            'playfair_display_source_sans' => 'Playfair Display + Source Sans 3',
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
        'title' => 'User provisioning',
        'description' => 'The password assigned to administratively created accounts',
        'label' => [
            'status' => 'Status',
            'default_password' => 'Default password',
            'default_password_confirmation' => 'Confirm default password',
        ],
        'placeholder' => [
            'default_password' => 'Default password',
            'default_password_confirmation' => 'Confirm default password',
        ],
        'help' => [
            'default_password' => 'Used only when an administrator creates an account. The account has to replace it at the first login.',
            'stored' => 'The stored password is never displayed again. Saving a new value replaces it.',
        ],
        'status' => [
            'configured' => 'Configured',
            'not_configured' => 'Not configured',
        ],
        'button' => [
            'save' => 'Save',
            'remove' => 'Remove default password',
            'cancel' => 'Cancel',
            'confirm_remove' => 'Remove',
        ],
        'confirmation' => [
            'title' => 'Remove the default password?',
            'description' => 'Creating an account is not possible until a new default password is configured.',
        ],

        'default_password' => [
            'policy_conflict' => 'The stored default password for new users does not satisfy the selected policy. Update the default password first.',
            'updated' => 'The default password was updated.',
            'removed' => 'The default password was removed.',
            'not_configured' => 'The default password for new users has not been configured.',
        ],
    ],

    'security' => [
        'title' => 'Security',
        'description' => 'Failed login handling and maintenance mode',
        'label' => [
            'max_failed_login_attempts' => 'Maximum failed login attempts',
            'suspension_duration_minutes' => 'Login throttle window (minutes)',
            'maintenance_enabled' => 'Maintenance mode',
        ],
        'help' => [
            'max_failed_login_attempts' => 'Maximum failed attempts allowed for one email and IP address.',
            'suspension_duration_minutes' => 'How long that email and IP address must wait before trying again.',
            'maintenance_enabled' => 'While enabled, the application answers with a maintenance notice.',
        ],
        'alert' => [
            'maintenance_title' => 'Maintenance mode',
            'maintenance' => 'Signing in and out, password recovery, and the settings screens stay reachable, and accounts holding the settings permission keep full access.',
        ],
        'button' => [
            'save' => 'Save',
        ],
        'message' => [
            'updated' => 'Security settings updated.',
        ],
    ],

    'mail' => [
        'title' => 'Mail',
        'description' => 'The sender identity used for outgoing email',
        'label' => [
            'from_name' => 'Sender name',
            'from_address' => 'Sender address',
        ],
        'placeholder' => [
            'from_name' => 'Sender name',
            'from_address' => 'sender@example.com',
        ],
        'help' => [
            'from_name' => 'Name shown as the sender of every message.',
            'from_address' => 'Address messages are sent from.',
            'test' => 'The test message is sent to the email address of the signed-in administrator.',
        ],
        'button' => [
            'save' => 'Save',
            'test' => 'Send test email',
        ],
        'message' => [
            'updated' => 'Mail settings updated.',
        ],

        'test' => [
            'subject' => 'Mail configuration test for :company',
            'heading' => 'Mail configuration test',
            'intro' => 'This message confirms that :company can deliver email with the current mail settings.',
            'sender' => 'Messages are sent as :name (:address).',
            'sent' => 'The test message was sent.',
        ],
    ],

    'chat' => [
        'title' => 'Chat',
        'description' => 'Availability of direct messages between users',
        'label' => [
            'chat_enabled' => 'Enable chat',
            'image_uploads_enabled' => 'Enable image uploads',
        ],
        'help' => [
            'chat_enabled' => 'While disabled, the chat page, the desktop widget, and chat notifications are closed for everyone. Conversations, messages, and audit records are kept.',
            'image_uploads_enabled' => 'Allow users to attach one private PNG, JPEG, or WebP image to a message. Existing images remain available when disabled.',
        ],
        'button' => [
            'save' => 'Save',
        ],
        'message' => [
            'updated' => 'Chat settings updated.',
        ],
    ],

];
