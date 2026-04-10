<?php

declare(strict_types=1);

namespace RuntimeShield\Tests\Unit\DTO\Report;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeShield\DTO\Report\SecurityReport;
use RuntimeShield\DTO\Rule\Severity;
use RuntimeShield\DTO\Rule\Violation;
use RuntimeShield\DTO\Rule\ViolationCollection;

final class SecurityReportGradeEdgeCaseTest extends TestCase
{
    #[Test]
    public function two_medium_violations_give_score_of_90(): void
    {
        $this->assertSame(90, $this->reportWith(Severity::MEDIUM, 2)->score());
    }

    #[Test]
    public function five_high_violations_give_score_of_50(): void
    {
        $this->assertSame(50, $this->reportWith(Severity::HIGH, 5)->score());
    }

    #[Test]
    public function d_grade_for_score_in_40_to_59_range(): void
    {
        $this->assertSame('D', $this->reportWith(Severity::HIGH, 4)->grade());
    }

    #[Test]
    public function c_grade_for_score_in_60_to_74_range(): void
    {
        $this->assertSame('C', $this->reportWith(Severity::MEDIUM, 8)->grade());
    }

    #[Test]
    public function b_grade_for_score_in_75_to_89_range(): void
    {
        $this->assertSame('B', $this->reportWith(Severity::MEDIUM, 5)->grade());
    }
    private function reportWith(Severity $severity, int $count): SecurityReport
    {
        $violations = array_fill(0, $count, new Violation('r', 'T', 'D', $severity));

        return new SecurityReport(
            scannedAt: new \DateTimeImmutable(),
            routeCount: 10,
            violations: new ViolationCollection($violations),
        );
    }
}
