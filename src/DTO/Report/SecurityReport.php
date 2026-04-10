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
    /** @param list<RouteProtection> $routeProtections */
    public function __construct(
        public readonly DateTimeImmutable $scannedAt,
        public readonly int $routeCount,
        public readonly ViolationCollection $violations,
        public readonly array $routeProtections = [],
    ) {
    }

    /** Number of routes with at least one violation. */
    public function exposedRouteCount(): int
    {
        $count = 0;

        foreach ($this->routeProtections as $protection) {
            if (! $protection->isFullyProtected()) {
                $count++;
            }
        }

        return $count;
    }
}
