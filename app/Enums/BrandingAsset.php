<?php

namespace App\Enums;

/**
 * The branding images an administrator can upload.
 *
 * Each case owns the settings property it persists to and the storage
 * directory it lives in, so the route parameter alone resolves both.
 */
enum BrandingAsset: string
{
    case Logo = 'logo';
    case LogoDark = 'logo-dark';
    case Icon = 'icon';
    case IconDark = 'icon-dark';
    case AuthBackground = 'auth-background';

    /**
     * The `BrandingSettings` property backing the asset.
     */
    public function property(): string
    {
        return match ($this) {
            self::Logo => 'logoPath',
            self::LogoDark => 'logoDarkPath',
            self::Icon => 'iconPath',
            self::IconDark => 'iconDarkPath',
            self::AuthBackground => 'authBackgroundPath',
        };
    }

    /**
     * The public disk directory the asset is stored in.
     */
    public function directory(): string
    {
        return match ($this) {
            self::Logo, self::LogoDark => 'branding/logos',
            self::Icon, self::IconDark => 'branding/icons',
            self::AuthBackground => 'branding/auth-backgrounds',
        };
    }
}
