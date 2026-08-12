<?php

namespace App\Support\Ci;

/**
 * The machine-checked evidence for one remediation ledger entry, gathered by
 * `.github/scripts/provenance-report.sh` with git and judged by
 * `php artisan ci:release-eligibility` from the report alone.
 *
 * An entry is `open` while the offending commit sits inside the inspected
 * range: it must then prove that the remediating commit comes after it in the
 * range and that the remediating commit is exactly the inverse of the
 * offending one. Once the effective baseline has moved past the offending
 * commit the entry is `closed`: the history was already judged and published,
 * so it is no longer treated as an open exception.
 */
final readonly class RemediationEvidence
{
    /**
     * @param  bool|null  $order  Whether the remediating commit follows the offending one in the range; null when not applicable.
     * @param  bool|null  $reverts  Whether the remediating commit is the exact inverse of the offending one; null when not applicable.
     */
    private function __construct(
        public string $status,
        public ?bool $order,
        public ?bool $reverts,
    ) {}

    /**
     * @param  array<array-key, mixed>  $evidence
     */
    public static function fromArray(array $evidence): self
    {
        return new self(
            CiPayload::string($evidence, 'status', 'unknown'),
            self::nullableBoolean($evidence, 'order'),
            self::nullableBoolean($evidence, 'reverts'),
        );
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    /**
     * Whether the gathered evidence proves the remediation: the remediating
     * commit follows the offending one and reverts it exactly.
     */
    public function proves(): bool
    {
        return $this->order === true && $this->reverts === true;
    }

    /**
     * @param  array<array-key, mixed>  $evidence
     */
    private static function nullableBoolean(array $evidence, string $key): ?bool
    {
        $value = $evidence[$key] ?? null;

        return is_bool($value) ? $value : null;
    }
}
