<?php

namespace App\Enums;

use InvalidArgumentException;

/**
 * The application surfaces a queued image upload can be published to.
 *
 * Every target owns the disk its finished image lands on and the box the
 * image is scaled down into. Nothing here crops or upscales: the box is an
 * upper bound, so a smaller image is published untouched.
 */
enum ImageUploadTarget: string
{
    case Avatar = 'avatar';
    case Branding = 'branding';
    case ChatImage = 'chat-image';
    case DocumentationImage = 'documentation-image';

    /**
     * The disk the published image is written to.
     *
     * Chat images stay private and are served through an authorized endpoint;
     * avatars, branding, and documentation images are public assets, because
     * documentation bodies are readable by every verified reader.
     */
    public function disk(): string
    {
        return match ($this) {
            self::Avatar, self::Branding, self::DocumentationImage => 'public',
            self::ChatImage => 'local',
        };
    }

    /**
     * Whether a second active upload for the same target must be refused.
     *
     * Avatar and branding each address one replaceable slot, so a concurrent
     * upload would race over it. Every chat image is its own new message, and
     * every documentation image is its own new node, so neither has anything to
     * contend with.
     */
    public function isExclusive(): bool
    {
        return match ($this) {
            self::Avatar, self::Branding => true,
            self::ChatImage, self::DocumentationImage => false,
        };
    }

    /**
     * The maximum width and height the published image is scaled down into.
     *
     * @return array{0: positive-int, 1: positive-int}
     */
    public function maxDimensions(?BrandingAsset $asset = null): array
    {
        return match ($this) {
            self::Avatar => [512, 512],
            self::ChatImage, self::DocumentationImage => [1600, 1600],
            self::Branding => ($asset ?? throw new InvalidArgumentException(
                'A branding upload must name the asset it belongs to.',
            ))->maxDimensions(),
        };
    }
}
