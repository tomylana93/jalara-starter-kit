<?php

namespace App\Support;

use Illuminate\Validation\Validator;

final class DocumentationContent
{
    /** @var list<string> */
    private const array ALLOWED_NODES = ['doc', 'paragraph', 'heading', 'text', 'bulletList', 'orderedList', 'listItem', 'blockquote', 'codeBlock', 'hardBreak', 'horizontalRule', 'table', 'tableRow', 'tableHeader', 'tableCell'];

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
