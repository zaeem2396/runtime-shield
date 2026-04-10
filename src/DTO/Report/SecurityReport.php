<?php

declare(strict_types=1);

namespace RuntimeShield\DTO\Report;

use DateTimeImmutable;
use RuntimeShield\DTO\Rule\ViolationCollection;

/**
 * Immutable aggregate report produced after scanning all routes.
 *
 * Contains the overall violation collection, per-route protection snapshots,
 * a computed security score, and a letter grade — all the data the CLI report
 * commands need to render their output.
 */
final class SecurityReport
{
    public function __construct(
        public readonly DateTimeImmutable $scannedAt,
        public readonly int $routeCount,
        public readonly ViolationCollection $violations,
    ) {
    }
}
