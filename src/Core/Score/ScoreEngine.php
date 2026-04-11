<?php

declare(strict_types=1);

namespace RuntimeShield\Core\Score;

use RuntimeShield\Contracts\Score\RuleCategoryMapContract;
use RuntimeShield\Contracts\Score\ScoreEngineContract;
use RuntimeShield\DTO\Rule\Severity;
use RuntimeShield\DTO\Rule\Violation;
use RuntimeShield\DTO\Rule\ViolationCollection;
use RuntimeShield\DTO\Score\CategoryScore;
use RuntimeShield\DTO\Score\ScoreCategory;
use RuntimeShield\DTO\Score\SecurityScore;

/**
 * Calculates a weighted SecurityScore from a ViolationCollection.
 *
 * Each violation is attributed to a ScoreCategory via the RuleCategoryMap.
 * Per-category scores start at 100 and deduct based on violation severity.
 * An overall weighted score is derived from configurable category weights.
 */
final class ScoreEngine implements ScoreEngineContract
{
    /** Point deduction per severity level within a category. */
    private const DEDUCTIONS = [
        'CRITICAL' => 20,
        'HIGH'     => 10,
        'MEDIUM'   =>  5,
        'LOW'      =>  2,
        'INFO'     =>  0,
    ];

    /**
     * @param array<string, int> $weights Override per-category weights (keyed by ScoreCategory::value).
     */
    public function __construct(
        private readonly RuleCategoryMapContract $categoryMap,
        private readonly array $weights = [],
    ) {
    }

    public function calculate(ViolationCollection $violations): SecurityScore
    {
        $grouped        = $this->groupByCategory($violations);
        $categoryScores = $this->buildCategoryScores($grouped);
        $overall        = $this->weightedOverall($categoryScores);

        return new SecurityScore(
            overall: $overall,
            grade: $this->grade($overall),
            categories: $categoryScores,
            totalViolations: $violations->count(),
        );
    }

    /**
     * Returns a summary array suitable for embedding in CLI/JSON output.
     *
     * @return array<string, mixed>
     */
    public function summarise(SecurityScore $score): array
    {
        return [
            'overall'   => $score->overall,
            'grade'     => $score->grade,
            'passed'    => count($score->passedCategories()),
            'failed'    => count($score->failedCategories()),
            'total'     => count($score->categories),
        ];
    }

    /**
     * Group violations by their resolved ScoreCategory.
     *
     * @return array<string, list<Violation>>
     */
    private function groupByCategory(ViolationCollection $violations): array
    {
        $grouped = [];

        foreach ($violations->all() as $violation) {
            $category = $this->categoryMap->categoryFor($violation->ruleId);

            if ($category !== null) {
                $grouped[$category->value][] = $violation;
            }
        }

        return $grouped;
    }

    /**
     * Build a CategoryScore for every ScoreCategory, even those with zero violations.
     *
     * @param  array<string, list<Violation>> $grouped
     * @return array<string, CategoryScore>
     */
    private function buildCategoryScores(array $grouped): array
    {
        $scores = [];

        foreach (ScoreCategory::cases() as $category) {
            $categoryViolations = $grouped[$category->value] ?? [];
            $score              = $this->categoryScore($categoryViolations);
            $weight             = $this->weights[$category->value] ?? $category->defaultWeight();

            $scores[$category->value] = new CategoryScore(
                category: $category,
                score: $score,
                maxScore: 100,
                violationCount: count($categoryViolations),
                weight: $weight,
            );
        }

        return $scores;
    }

    /**
     * Deduct points from 100 based on each violation's severity.
     *
     * @param list<Violation> $violations
     */
    private function categoryScore(array $violations): int
    {
        $score = 100;

        foreach ($violations as $violation) {
            $score -= self::DEDUCTIONS[$violation->severity->name] ?? 0;
        }

        return max(0, $score);
    }

    /**
     * Calculate the weighted average overall score from per-category scores.
     *
     * @param array<string, CategoryScore> $categoryScores
     */
    private function weightedOverall(array $categoryScores): int
    {
        $totalWeight  = 0;
        $weightedSum  = 0.0;

        foreach ($categoryScores as $cs) {
            $totalWeight += $cs->weight;
            $weightedSum += $cs->score * $cs->weight;
        }

        if ($totalWeight === 0) {
            return 100;
        }

        return (int) round($weightedSum / $totalWeight);
    }

    /** Map an overall score to a letter grade. */
    private function grade(int $score): string
    {
        return match (true) {
            $score >= 90 => 'A',
            $score >= 75 => 'B',
            $score >= 60 => 'C',
            $score >= 40 => 'D',
            default      => 'F',
        };
    }
}
