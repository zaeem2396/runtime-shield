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

    /**
     * Security score 0–100.
     * Deductions: CRITICAL −20, HIGH −10, MEDIUM −5, LOW −2.
     */
    public function score(): int
    {
        $deductions = 0;
        $deductions += count($this->violations->critical()) * 20;
        $deductions += count($this->violations->high()) * 10;
        $deductions += count($this->violations->medium()) * 5;
        $deductions += count($this->violations->low()) * 2;

        return max(0, 100 - $deductions);
    }

    /** Letter grade derived from score(). */
    public function grade(): string
    {
        return match (true) {
            $this->score() >= 90 => 'A',
            $this->score() >= 75 => 'B',
            $this->score() >= 60 => 'C',
            $this->score() >= 40 => 'D',
            default              => 'F',
        };
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
