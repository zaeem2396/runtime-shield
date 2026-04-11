<?php

declare(strict_types=1);

namespace Tests\Unit\DTO\Score;

use PHPUnit\Framework\TestCase;
use RuntimeShield\DTO\Score\CategoryScore;
use RuntimeShield\DTO\Score\ScoreCategory;
use RuntimeShield\DTO\Score\SecurityScore;

final class SecurityScoreTest extends TestCase
{
    /**
     * @param array<string, CategoryScore> $categories
     */
    private function make(
        int $overall = 85,
        string $grade = 'B',
        array $categories = [],
        int $totalViolations = 0,
    ): SecurityScore {
        return new SecurityScore($overall, $grade, $categories, $totalViolations);
    }

    private function makeCategory(ScoreCategory $category, int $score, int $violations = 0): CategoryScore
    {
        return new CategoryScore($category, $score, 100, $violations, $category->defaultWeight());
    }

    public function test_basic_properties_are_stored(): void
    {
        $score = $this->make(72, 'C', [], 5);

        $this->assertSame(72, $score->overall);
        $this->assertSame('C', $score->grade);
        $this->assertSame(5, $score->totalViolations);
        $this->assertSame([], $score->categories);
    }

    public function test_category_score_lookup_returns_correct_category(): void
    {
        $auth = $this->makeCategory(ScoreCategory::AUTH, 60);

        $score = $this->make(categories: [ScoreCategory::AUTH->value => $auth]);

        $result = $score->categoryScore(ScoreCategory::AUTH);
        $this->assertNotNull($result);
        $this->assertSame(60, $result->score);
    }

    public function test_category_score_lookup_returns_null_for_missing(): void
    {
        $score = $this->make();
        $this->assertNull($score->categoryScore(ScoreCategory::AUTH));
    }

    public function test_passed_categories_returns_categories_with_score_ge_75(): void
    {
        $categories = [
            ScoreCategory::AUTH->value       => $this->makeCategory(ScoreCategory::AUTH, 80),
            ScoreCategory::CSRF->value       => $this->makeCategory(ScoreCategory::CSRF, 70),
            ScoreCategory::RATE_LIMIT->value => $this->makeCategory(ScoreCategory::RATE_LIMIT, 75),
        ];

        $score  = $this->make(categories: $categories);
        $passed = $score->passedCategories();

        $this->assertCount(2, $passed);
    }

    public function test_failed_categories_returns_categories_with_score_lt_75(): void
    {
        $categories = [
            ScoreCategory::AUTH->value => $this->makeCategory(ScoreCategory::AUTH, 40),
            ScoreCategory::CSRF->value => $this->makeCategory(ScoreCategory::CSRF, 90),
        ];

        $score  = $this->make(categories: $categories);
        $failed = $score->failedCategories();

        $this->assertCount(1, $failed);
        $this->assertSame(ScoreCategory::AUTH, $failed[0]->category);
    }

    public function test_has_critical_failures_true_when_any_score_is_zero(): void
    {
        $categories = [
            ScoreCategory::AUTH->value => $this->makeCategory(ScoreCategory::AUTH, 0),
            ScoreCategory::CSRF->value => $this->makeCategory(ScoreCategory::CSRF, 90),
        ];

        $score = $this->make(categories: $categories);
        $this->assertTrue($score->hasCriticalFailures());
    }

    public function test_has_critical_failures_false_when_all_scores_above_zero(): void
    {
        $categories = [
            ScoreCategory::AUTH->value => $this->makeCategory(ScoreCategory::AUTH, 20),
            ScoreCategory::CSRF->value => $this->makeCategory(ScoreCategory::CSRF, 80),
        ];

        $score = $this->make(categories: $categories);
        $this->assertFalse($score->hasCriticalFailures());
    }

    public function test_highest_risk_returns_category_with_lowest_score(): void
    {
        $categories = [
            ScoreCategory::AUTH->value       => $this->makeCategory(ScoreCategory::AUTH, 90),
            ScoreCategory::CSRF->value       => $this->makeCategory(ScoreCategory::CSRF, 30),
            ScoreCategory::RATE_LIMIT->value => $this->makeCategory(ScoreCategory::RATE_LIMIT, 60),
        ];

        $score = $this->make(categories: $categories);
        $risk  = $score->highestRisk();

        $this->assertNotNull($risk);
        $this->assertSame(ScoreCategory::CSRF, $risk->category);
    }

    public function test_highest_risk_returns_null_for_empty_categories(): void
    {
        $this->assertNull($this->make()->highestRisk());
    }

    public function test_to_array_contains_expected_keys(): void
    {
        $arr = $this->make(80, 'B', [], 3)->toArray();

        $this->assertArrayHasKey('overall', $arr);
        $this->assertArrayHasKey('grade', $arr);
        $this->assertArrayHasKey('total_violations', $arr);
        $this->assertArrayHasKey('categories', $arr);
    }

    public function test_to_array_categories_are_listed(): void
    {
        $categories = [
            ScoreCategory::AUTH->value => $this->makeCategory(ScoreCategory::AUTH, 75),
        ];

        $arr = $this->make(categories: $categories)->toArray();

        $this->assertCount(1, $arr['categories']);
    }
}
