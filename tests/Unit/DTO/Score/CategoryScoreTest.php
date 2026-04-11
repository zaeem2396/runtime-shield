<?php

declare(strict_types=1);

namespace Tests\Unit\DTO\Score;

use PHPUnit\Framework\TestCase;
use RuntimeShield\DTO\Score\CategoryScore;
use RuntimeShield\DTO\Score\ScoreCategory;

final class CategoryScoreTest extends TestCase
{
    public function test_percentage_returns_100_when_score_equals_max(): void
    {
        $this->assertSame(100.0, $this->make(100, 100)->percentage());
    }

    public function test_percentage_returns_0_when_score_is_0(): void
    {
        $this->assertSame(0.0, $this->make(0)->percentage());
    }

    public function test_percentage_returns_50_for_half_score(): void
    {
        $this->assertSame(50.0, $this->make(50, 100)->percentage());
    }

    public function test_percentage_returns_0_when_max_score_is_0(): void
    {
        $cs = new CategoryScore(ScoreCategory::AUTH, 0, 0, 0, 20);
        $this->assertSame(0.0, $cs->percentage());
    }

    public function test_is_passing_true_when_score_equals_75(): void
    {
        $this->assertTrue($this->make(75)->isPassing());
    }

    public function test_is_passing_true_when_score_above_75(): void
    {
        $this->assertTrue($this->make(100)->isPassing());
    }

    public function test_is_passing_false_when_score_below_75(): void
    {
        $this->assertFalse($this->make(74)->isPassing());
    }

    public function test_to_array_contains_expected_keys(): void
    {
        $arr = $this->make(80, 100, 2, 30)->toArray();

        $this->assertArrayHasKey('category', $arr);
        $this->assertArrayHasKey('label', $arr);
        $this->assertArrayHasKey('score', $arr);
        $this->assertArrayHasKey('max_score', $arr);
        $this->assertArrayHasKey('percentage', $arr);
        $this->assertArrayHasKey('weight', $arr);
        $this->assertArrayHasKey('violation_count', $arr);
        $this->assertArrayHasKey('passing', $arr);
    }

    public function test_to_array_values_are_correct(): void
    {
        $cs = $this->make(80, 100, 2, 30);
        $arr = $cs->toArray();

        $this->assertSame('auth', $arr['category']);
        $this->assertSame(80, $arr['score']);
        $this->assertSame(100, $arr['max_score']);
        $this->assertSame(80.0, $arr['percentage']);
        $this->assertSame(30, $arr['weight']);
        $this->assertSame(2, $arr['violation_count']);
        $this->assertTrue($arr['passing']);
    }
    private function make(
        int $score,
        int $maxScore = 100,
        int $violationCount = 0,
        int $weight = 20,
    ): CategoryScore {
        return new CategoryScore(
            category: ScoreCategory::AUTH,
            score: $score,
            maxScore: $maxScore,
            violationCount: $violationCount,
            weight: $weight,
        );
    }
}
