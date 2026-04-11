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

    /**
     * Look up the CategoryScore for a specific category.
     * Returns null when the category is not present (e.g. engine ran with partial categories).
     */
    public function categoryScore(ScoreCategory $category): CategoryScore|null
    {
        return $this->categories[$category->value] ?? null;
    }

    /**
     * Categories whose score is >= 75 (passing threshold).
     *
     * @return list<CategoryScore>
     */
    public function passedCategories(): array
    {
        return array_values(
            array_filter($this->categories, static fn (CategoryScore $cs): bool => $cs->isPassing()),
        );
    }

    /**
     * Categories whose score is < 75 (failing threshold).
     *
     * @return list<CategoryScore>
     */
    public function failedCategories(): array
    {
        return array_values(
            array_filter($this->categories, static fn (CategoryScore $cs): bool => ! $cs->isPassing()),
        );
    }

    /**
     * Whether any category has a score of 0 — indicates a completely unprotected area.
     */
    public function hasCriticalFailures(): bool
    {
        foreach ($this->categories as $cs) {
            if ($cs->score === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'overall'          => $this->overall,
            'grade'            => $this->grade,
            'total_violations' => $this->totalViolations,
            'categories'       => array_map(
                static fn (CategoryScore $cs): array => $cs->toArray(),
                array_values($this->categories),
            ),
        ];
    }
}
