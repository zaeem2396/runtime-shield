<?php

declare(strict_types=1);

namespace RuntimeShield\DTO\Score;

/**
 * Immutable aggregate security score produced by the ScoreEngine.
 *
 * Carries the weighted overall score (0–100), a letter grade, a per-category
 * breakdown, and the total number of violations that influenced the score.
 */
final class SecurityScore
{
    /**
     * @param array<string, CategoryScore> $categories Keyed by ScoreCategory::value
     */
    public function __construct(
        public readonly int $overall,
        public readonly string $grade,
        public readonly array $categories,
        public readonly int $totalViolations,
    ) {
    }
}
