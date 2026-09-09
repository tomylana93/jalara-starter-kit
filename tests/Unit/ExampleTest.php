<?php

declare(strict_types=1);

it('compares a computed value', function (): void {
    expect(strtolower('TRUE'))->toBe('true');
});
