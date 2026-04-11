<?php

declare(strict_types=1);

namespace Tests\Unit\DTO\Score;

use PHPUnit\Framework\TestCase;
use RuntimeShield\DTO\Score\CategoryScore;
use RuntimeShield\DTO\Score\ScoreCategory;
use RuntimeShield\DTO\Score\SecurityScore;

final class SecurityScoreEdgeCaseTest extends TestCase
{
    private function makeCategory(ScoreCategory $category, int $score): CategoryScore
    {
        return new CategoryScore($category, $score, 100, 0, $category->defaultWeight());
    }

    public function test_passed_categories_empty_when_all_fail(): void
    {
        $categories = [];

        foreach (ScoreCategory::cases() as $category) {
            $categories[$category->value] = $this->makeCategory($category, 0);
        }

        $score = new SecurityScore(0, 'F', $categories, 10);

        $this->assertEmpty($score->passedCategories());
    }

    public function test_failed_categories_empty_when_all_pass(): void
    {
        $categories = [];

        foreach (ScoreCategory::cases() as $category) {
            $categories[$category->value] = $this->makeCategory($category, 100);
        }

        $score = new SecurityScore(100, 'A', $categories, 0);

        $this->assertEmpty($score->failedCategories());
    }

    public function test_passed_and_failed_partitions_are_exhaustive(): void
    {
        $categories = [];

        foreach (ScoreCategory::cases() as $i => $category) {
            $categories[$category->value] = $this->makeCategory($category, $i % 2 === 0 ? 80 : 50);
        }

        $score = new SecurityScore(70, 'C', $categories, 2);

        $total = count($score->passedCategories()) + count($score->failedCategories());
        $this->assertSame(count(ScoreCategory::cases()), $total);
    }

    public function test_highest_risk_returns_only_category_when_one_exists(): void
    {
        $auth  = $this->makeCategory(ScoreCategory::AUTH, 40);
        $score = new SecurityScore(40, 'D', [ScoreCategory::AUTH->value => $auth], 3);

        $risk = $score->highestRisk();
        $this->assertNotNull($risk);
        $this->assertSame(ScoreCategory::AUTH, $risk->category);
    }

    public function test_to_array_total_violations_matches(): void
    {
        $score = new SecurityScore(90, 'A', [], 7);
        $arr   = $score->toArray();

        $this->assertSame(7, $arr['total_violations']);
    }

    public function test_to_array_overall_matches(): void
    {
        $score = new SecurityScore(55, 'C', [], 0);
        $arr   = $score->toArray();

        $this->assertSame(55, $arr['overall']);
    }

    public function test_to_array_grade_matches(): void
    {
        $score = new SecurityScore(45, 'D', [], 0);
        $arr   = $score->toArray();

        $this->assertSame('D', $arr['grade']);
    }
}
