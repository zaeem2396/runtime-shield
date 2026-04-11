<?php

declare(strict_types=1);

namespace Tests\Unit\DTO\Score;

use PHPUnit\Framework\TestCase;
use RuntimeShield\DTO\Score\SecurityScore;

final class SecurityScoreFormattedTest extends TestCase
{
    public function test_formatted_returns_score_over_100(): void
    {
        $score = new SecurityScore(78, 'C', [], 0);
        $this->assertSame('78/100', $score->formatted());
    }

    public function test_formatted_100_returns_100_over_100(): void
    {
        $score = new SecurityScore(100, 'A', [], 0);
        $this->assertSame('100/100', $score->formatted());
    }

    public function test_formatted_0_returns_0_over_100(): void
    {
        $score = new SecurityScore(0, 'F', [], 0);
        $this->assertSame('0/100', $score->formatted());
    }
}
