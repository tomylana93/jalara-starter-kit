<?php

namespace App\Concerns;

use App\Contracts\HasLabel;
use BackedEnum;
use LogicException;

/**
 * @phpstan-require-implements BackedEnum
 * @phpstan-require-implements HasLabel
 */
trait HasOptions
{
    /**
     * Get the enum cases as options.
     *
     * @param  array<string, string>  $additionalFields
     * @return list<array<string, mixed>>
     */
    public static function options(array $additionalFields = []): array
    {
        return array_map(
            function (self $case) use ($additionalFields): array {
                $option = [
                    'label' => $case->label(),
                    'value' => $case->value,
                ];

                foreach ($additionalFields as $field => $method) {
                    if (! is_callable([$case, $method])) {
                        throw new LogicException(sprintf(
                            'Method [%s] does not exist on enum [%s].',
                            $method,
                            $case::class,
                        ));
                    }

                    $option[$field] = call_user_func([$case, $method]);
                }

                return $option;
            },
            self::cases(),
        );
    }
}
