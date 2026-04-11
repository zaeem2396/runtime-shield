<?php

declare(strict_types=1);

namespace RuntimeShield\DTO\Score;

/**
 * Immutable score record for a single security category.
 *
 * Produced by ScoreEngine for each ScoreCategory and aggregated into the
 * parent SecurityScore DTO.
 */
final class CategoryScore
{
    public function __construct(
        public readonly ScoreCategory $category,
        public readonly int $score,
        public readonly int $maxScore,
        public readonly int $violationCount,
    ) {
    }
}
