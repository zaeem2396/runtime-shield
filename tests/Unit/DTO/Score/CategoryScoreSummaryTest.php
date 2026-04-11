<?php

declare(strict_types=1);

namespace Tests\Unit\DTO\Score;

use PHPUnit\Framework\TestCase;
use RuntimeShield\DTO\Score\CategoryScore;
use RuntimeShield\DTO\Score\ScoreCategory;

final class CategoryScoreSummaryTest extends TestCase
{
    private function make(int $score): CategoryScore
    {
        return new CategoryScore(ScoreCategory::AUTH, $score, 100, 0, 30);
    }

    public function test_summary_contains_label(): void
    {
        $cs = $this->make(80);
        $this->assertStringContainsString('Authentication', $cs->summary());
    }

    public function test_summary_contains_score(): void
    {
        $cs = $this->make(80);
        $this->assertStringContainsString('80/100', $cs->summary());
    }

    public function test_summary_contains_passing_when_score_ge_75(): void
    {
        $cs = $this->make(75);
        $this->assertStringContainsString('passing', $cs->summary());
    }

    public function test_summary_contains_failing_when_score_lt_75(): void
    {
        $cs = $this->make(74);
        $this->assertStringContainsString('failing', $cs->summary());
    }
}
