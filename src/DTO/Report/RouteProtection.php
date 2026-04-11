<?php

declare(strict_types=1);

namespace RuntimeShield\DTO\Report;

use RuntimeShield\DTO\Rule\Severity;
use RuntimeShield\DTO\Rule\ViolationCollection;

/**
 * Immutable snapshot of a single route's security protection coverage.
 *
 * Produced by RouteProtectionAnalyzer for every scanned route. Carries both
 * the middleware coverage booleans and the violation data from rule evaluation
 * so the CLI commands have a single, self-contained object per route.
 */
final class RouteProtection
{
    public function __construct(
        public readonly string $method,
        public readonly string $uri,
        public readonly string $name,
        public readonly bool $hasAuth,
        public readonly bool $hasCsrf,
        public readonly bool $hasRateLimit,
        public readonly ViolationCollection $violations,
    ) {
    }

    public function violationCount(): int
    {
        return $this->violations->count();
    }

    public function highestSeverity(): Severity|null
    {
        if ($this->violations->isEmpty()) {
            return null;
        }

        $sorted = $this->violations->sorted();

        return $sorted[0]->severity;
    }

    public function isFullyProtected(): bool
    {
        return $this->violations->isEmpty();
    }

    /**
     * Human-readable risk label derived from the highest violation severity.
     */
    public function riskLabel(): string
    {
        $severity = $this->highestSeverity();

        if ($severity === null) {
            return 'SAFE';
        }

        return match ($severity) {
            Severity::CRITICAL => 'CRITICAL',
            Severity::HIGH => 'HIGH RISK',
            Severity::MEDIUM => 'MEDIUM RISK',
            Severity::LOW => 'LOW RISK',
            Severity::INFO => 'INFO',
        };
    }
}
