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
        public readonly int $weight,
    ) {
    }

    /** Score as a percentage of maxScore (0.0 – 100.0). */
    public function percentage(): float
    {
        if ($this->maxScore === 0) {
            return 0.0;
        }

        return ($this->score / $this->maxScore) * 100.0;
    }

    /**
     * Whether this category meets the passing threshold (score >= 75).
     */
    public function isPassing(): bool
    {
        return $this->score >= 75;
    }

    /**
     * Human-readable summary string, e.g. "Authentication: 80/100 (passing)".
     */
    public function summary(): string
    {
        $status = $this->isPassing() ? 'passing' : 'failing';

        return sprintf('%s: %d/100 (%s)', $this->category->label(), $this->score, $status);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'category'        => $this->category->value,
            'label'           => $this->category->label(),
            'score'           => $this->score,
            'max_score'       => $this->maxScore,
            'percentage'      => round($this->percentage(), 1),
            'weight'          => $this->weight,
            'violation_count' => $this->violationCount,
            'passing'         => $this->isPassing(),
        ];
    }
}
