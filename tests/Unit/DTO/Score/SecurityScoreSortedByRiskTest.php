<?php

declare(strict_types=1);

namespace Tests\Unit\DTO\Score;

use PHPUnit\Framework\TestCase;
use RuntimeShield\DTO\Score\CategoryScore;
use RuntimeShield\DTO\Score\ScoreCategory;
use RuntimeShield\DTO\Score\SecurityScore;

final class SecurityScoreSortedByRiskTest extends TestCase
{
    public function test_sorted_by_risk_returns_ascending_scores(): void
    {
        $categories = [
            ScoreCategory::AUTH->value => $this->makeCategory(ScoreCategory::AUTH, 90),
            ScoreCategory::CSRF->value => $this->makeCategory(ScoreCategory::CSRF, 40),
            ScoreCategory::RATE_LIMIT->value => $this->makeCategory(ScoreCategory::RATE_LIMIT, 70),
        ];

        $score = new SecurityScore(70, 'C', $categories, 3);
        $sorted = $score->sortedByRisk();

        $this->assertSame(40, $sorted[0]->score);
        $this->assertSame(70, $sorted[1]->score);
        $this->assertSame(90, $sorted[2]->score);
    }

    public function test_sorted_by_risk_returns_all_categories(): void
    {
        $categories = [];

        foreach (ScoreCategory::cases() as $i => $category) {
            $categories[$category->value] = $this->makeCategory($category, ($i + 1) * 20);
        }

        $score = new SecurityScore(60, 'C', $categories, 0);
        $sorted = $score->sortedByRisk();

        $this->assertCount(5, $sorted);
    }

    public function test_sorted_by_risk_empty_when_no_categories(): void
    {
        $score = new SecurityScore(100, 'A', [], 0);
        $sorted = $score->sortedByRisk();

        $this->assertEmpty($sorted);
    }

    public function test_first_element_is_highest_risk(): void
    {
        $categories = [
            ScoreCategory::AUTH->value => $this->makeCategory(ScoreCategory::AUTH, 10),
            ScoreCategory::CSRF->value => $this->makeCategory(ScoreCategory::CSRF, 80),
        ];

        $score = new SecurityScore(45, 'D', $categories, 5);
        $sorted = $score->sortedByRisk();

        $this->assertSame(ScoreCategory::AUTH, $sorted[0]->category);
    }
    private function makeCategory(ScoreCategory $category, int $score): CategoryScore
    {
        return new CategoryScore($category, $score, 100, 0, $category->defaultWeight());
    }
}
