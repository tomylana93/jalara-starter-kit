<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Validator;

final class DocumentationContent
{
    /**
     * The public-disk directory every documentation image is published into.
     *
     * A persisted image node may name nothing else. Confining `src` to one
     * managed prefix is what keeps a document from smuggling in a remote
     * tracking URL, a `data:` payload, or a path pointing at another feature's
     * private media.
     */
    public const string IMAGE_DIRECTORY = 'documentation';

    /**
     * The disk documentation images are published to.
     */
    public const string IMAGE_DISK = 'public';

    /**
     * The largest image an author may hand to the upload endpoint.
     *
     * These bound the *input*, independently of the 1600x1600 box the queue
     * scales the published image down into.
     */
    public const int IMAGE_MAX_KILOBYTES = 2048;

    public const int IMAGE_MAX_DIMENSION = 2048;

    /**
     * The longest alt text a document may carry per image.
     */
    private const int MAX_ALT_LENGTH = 300;

    /** @var list<string> */
    private const array ALLOWED_NODES = ['doc', 'paragraph', 'heading', 'text', 'bulletList', 'orderedList', 'listItem', 'blockquote', 'codeBlock', 'hardBreak', 'horizontalRule', 'image', 'table', 'tableRow', 'tableHeader', 'tableCell'];

    /** @var list<string> */
    private const array ALLOWED_MARKS = ['bold', 'italic', 'strike', 'code', 'link'];

    /** @param array<string, mixed> $document */
    public static function validate(array $document, Validator $validator): void
    {
        if (($document['type'] ?? null) !== 'doc') {
            $validator->errors()->add('content', __('documentation.validation.invalid_content'));

            return;
        }

        self::validateNode($document, $validator);
    }

    /** @param array<string, mixed> $document */
    public static function text(array $document): string
    {
        $parts = [];
        self::collectText($document, $parts);

        return trim(preg_replace('/\s+/u', ' ', implode(' ', $parts)) ?? '');
    }

    /** @param array<string, mixed> $node */
    private static function validateNode(array $node, Validator $validator): void
    {
        $type = $node['type'] ?? null;

        if (! is_string($type) || ! in_array($type, self::ALLOWED_NODES, true)) {
            $validator->errors()->add('content', __('documentation.validation.invalid_content'));

            return;
        }

        if ($type === 'heading' && ! in_array($node['attrs']['level'] ?? null, [1, 2, 3], true)) {
            $validator->errors()->add('content', __('documentation.validation.invalid_heading'));
        }

        if ($type === 'image' && ! self::validImage($node['attrs'] ?? null)) {
            $validator->errors()->add('content', __('documentation.validation.invalid_image'));
        }

        $marks = $node['marks'] ?? [];
        $children = $node['content'] ?? [];

        if (! is_array($marks) || ! is_array($children)) {
            $validator->errors()->add('content', __('documentation.validation.invalid_content'));

            return;
        }

        foreach ($marks as $mark) {
            if (! is_array($mark) || ! in_array($mark['type'] ?? null, self::ALLOWED_MARKS, true)) {
                $validator->errors()->add('content', __('documentation.validation.invalid_content'));

                continue;
            }

            if ($mark['type'] === 'link' && ! self::validLink($mark['attrs']['href'] ?? null)) {
                $validator->errors()->add('content', __('documentation.validation.invalid_link'));
            }
        }

        foreach ($children as $child) {
            if (is_array($child)) {
                self::validateNode($child, $validator);
            } else {
                $validator->errors()->add('content', __('documentation.validation.invalid_content'));
            }
        }
    }

    private static function validLink(mixed $href): bool
    {
        if (! is_string($href) || $href === '') {
            return false;
        }

        if (str_starts_with($href, '/')) {
            /* A protocol-relative "//host" is an external destination, not an internal path. */
            return ! str_starts_with($href, '//');
        }

        return filter_var($href, FILTER_VALIDATE_URL) !== false
            && in_array(parse_url($href, PHP_URL_SCHEME), ['http', 'https'], true);
    }

    /**
     * Whether an image node's attributes may be persisted.
     *
     * Alt text is mandatory rather than optional: a documentation body is read
     * by people who cannot see the image, and the editor always collects one.
     */
    private static function validImage(mixed $attrs): bool
    {
        if (! is_array($attrs) || self::imageRelativePath($attrs['src'] ?? null) === null) {
            return false;
        }

        $alt = $attrs['alt'] ?? null;

        return is_string($alt)
            && trim($alt) !== ''
            && mb_strlen($alt) <= self::MAX_ALT_LENGTH;
    }

    /**
     * The disk-relative path an image `src` names, when it names a managed one.
     *
     * Returning null is the single definition of "this image is not ours",
     * shared by content validation and the orphan sweep so the two can never
     * disagree about which files a document protects.
     */
    private static function imageRelativePath(mixed $src): ?string
    {
        if (! is_string($src) || $src === '') {
            return null;
        }

        $prefix = Storage::disk(self::IMAGE_DISK)->url(self::IMAGE_DIRECTORY).'/';

        if (! str_starts_with($src, $prefix)) {
            return null;
        }

        $relative = substr($src, strlen($prefix));

        /*
         * A query or fragment would let an otherwise valid path carry an
         * arbitrary payload, and `..` would climb out of the managed prefix.
         */
        if ($relative === ''
            || str_contains($relative, '..')
            || str_contains($relative, '?')
            || str_contains($relative, '#')
            || preg_match('/[\x00-\x1f]/', $relative) === 1) {
            return null;
        }

        return self::IMAGE_DIRECTORY.'/'.$relative;
    }

    /**
     * Every managed image path a stored document still points at.
     *
     * The orphan sweep reads this to decide which published images are still in
     * use; anything it does not return is a candidate once the grace period has
     * passed.
     *
     * @param  array<string, mixed>  $document
     * @return list<string>
     */
    public static function imagePaths(array $document): array
    {
        $paths = [];
        self::collectImagePaths($document, $paths);

        return array_values(array_unique($paths));
    }

    /**
     * @param  array<string, mixed>  $node
     * @param  list<string>  $paths
     */
    private static function collectImagePaths(array $node, array &$paths): void
    {
        if (($node['type'] ?? null) === 'image') {
            $path = self::imageRelativePath($node['attrs']['src'] ?? null);

            if ($path !== null) {
                $paths[] = $path;
            }
        }

        foreach ($node['content'] ?? [] as $child) {
            if (is_array($child)) {
                self::collectImagePaths($child, $paths);
            }
        }
    }

    /** @param array<string, mixed> $node
     * @param  list<string>  $parts
     */
    private static function collectText(array $node, array &$parts): void
    {
        if (($node['type'] ?? null) === 'text' && is_string($node['text'] ?? null)) {
            $parts[] = $node['text'];
        }

        foreach ($node['content'] ?? [] as $child) {
            if (is_array($child)) {
                self::collectText($child, $parts);
            }
        }
    }
}
