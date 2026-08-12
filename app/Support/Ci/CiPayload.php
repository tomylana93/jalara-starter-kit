<?php

namespace App\Support\Ci;

use JsonException;
use RuntimeException;

/**
 * Typed reads over the untyped JSON that GitHub's API and event payloads hand
 * to the continuous integration commands.
 *
 * Every accessor is total: a missing or wrongly typed key yields the neutral
 * value for its type rather than an error, so a policy check reports the rule
 * that failed instead of a decoding crash.
 */
final class CiPayload
{
    /**
     * @return array<array-key, mixed>
     */
    public static function readFile(string $path): array
    {
        $contents = @file_get_contents($path);

        throw_if($contents === false, RuntimeException::class, "Unable to read the JSON file at [{$path}].");

        try {
            $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $jsonException) {
            throw new RuntimeException("The JSON file at [{$path}] is not valid JSON: {$jsonException->getMessage()}", $jsonException->getCode(), $jsonException);
        }

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function string(array $data, string $key, string $default = ''): string
    {
        $value = $data[$key] ?? null;

        return is_string($value) ? $value : $default;
    }

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function integer(array $data, string $key, int $default = 0): int
    {
        $value = $data[$key] ?? null;

        return is_int($value) ? $value : $default;
    }

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function boolean(array $data, string $key, bool $default = false): bool
    {
        $value = $data[$key] ?? null;

        return is_bool($value) ? $value : $default;
    }

    /**
     * @param  array<array-key, mixed>  $data
     * @return array<array-key, mixed>
     */
    public static function map(array $data, string $key): array
    {
        $value = $data[$key] ?? null;

        return is_array($value) ? $value : [];
    }

    /**
     * @param  array<array-key, mixed>  $data
     * @return list<array<array-key, mixed>>
     */
    public static function maps(array $data, string $key): array
    {
        $entries = [];

        foreach (self::map($data, $key) as $entry) {
            if (is_array($entry)) {
                $entries[] = $entry;
            }
        }

        return $entries;
    }

    /**
     * @param  array<array-key, mixed>  $data
     * @return list<string>
     */
    public static function strings(array $data, string $key): array
    {
        $values = [];

        foreach (self::map($data, $key) as $value) {
            if (is_string($value)) {
                $values[] = $value;
            }
        }

        return $values;
    }
}
