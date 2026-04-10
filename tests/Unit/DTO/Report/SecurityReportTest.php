<?php

declare(strict_types=1);

namespace RuntimeShield\Tests\Unit\DTO\Report;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeShield\DTO\Report\SecurityReport;
use RuntimeShield\DTO\Rule\Severity;
use RuntimeShield\DTO\Rule\Violation;
use RuntimeShield\DTO\Rule\ViolationCollection;

final class SecurityReportTest extends TestCase
{
    #[Test]
    public function score_is_100_with_no_violations(): void
    {
        $report = $this->makeReport(new ViolationCollection());

        $this->assertSame(100, $report->score());
        $this->assertSame('A', $report->grade());
    }

    #[Test]
    public function critical_violation_deducts_20_points(): void
    {
        $report = $this->makeReport(new ViolationCollection([$this->violation(Severity::CRITICAL)]));

        $this->assertSame(80, $report->score());
    }

    #[Test]
    public function high_violation_deducts_10_points(): void
    {
        $report = $this->makeReport(new ViolationCollection([$this->violation(Severity::HIGH)]));

        $this->assertSame(90, $report->score());
        $this->assertSame('A', $report->grade());
    }

    #[Test]
    public function score_never_goes_below_zero(): void
    {
        $violations = array_fill(0, 10, $this->violation(Severity::CRITICAL));
        $report = $this->makeReport(new ViolationCollection($violations));

        $this->assertSame(0, $report->score());
        $this->assertSame('F', $report->grade());
    }

    #[Test]
    public function grade_reflects_score_thresholds(): void
    {
        $withViolations = static fn (int $n, Severity $s): SecurityReport => new SecurityReport(
            new \DateTimeImmutable(),
            10,
            new ViolationCollection(array_fill(0, $n, new Violation('r', 'T', 'D', $s))),
        );

        $this->assertSame('A', $withViolations(0, Severity::INFO)->grade());
        $this->assertSame('B', $withViolations(1, Severity::MEDIUM)->grade());
        $this->assertSame('F', $withViolations(5, Severity::CRITICAL)->grade());
    }

    #[Test]
    public function to_array_contains_expected_keys(): void
    {
        $arr = $this->makeReport(new ViolationCollection())->toArray();

        $this->assertArrayHasKey('scanned_at', $arr);
        $this->assertArrayHasKey('route_count', $arr);
        $this->assertArrayHasKey('score', $arr);
        $this->assertArrayHasKey('grade', $arr);
        $this->assertArrayHasKey('total_violations', $arr);
        $this->assertArrayHasKey('violations', $arr);
    }

    #[Test]
    public function to_array_serializes_severity_counts(): void
    {
        $report = $this->makeReport(new ViolationCollection([
            $this->violation(Severity::CRITICAL),
            $this->violation(Severity::HIGH),
        ]));

        $arr = $report->toArray();

        $this->assertSame(1, $arr['critical']);
        $this->assertSame(1, $arr['high']);
        $this->assertSame(0, $arr['medium']);
    }
    private function violation(Severity $severity): Violation
    {
        return new Violation('rule', 'Title', 'Desc', $severity);
    }

    private function makeReport(ViolationCollection $violations): SecurityReport
    {
        return new SecurityReport(
            scannedAt: new \DateTimeImmutable(),
            routeCount: 10,
            violations: $violations,
        );
    }
}
