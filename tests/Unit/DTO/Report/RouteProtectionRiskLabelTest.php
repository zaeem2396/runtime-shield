<?php

declare(strict_types=1);

namespace RuntimeShield\Tests\Unit\DTO\Report;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeShield\DTO\Report\RouteProtection;
use RuntimeShield\DTO\Rule\Severity;
use RuntimeShield\DTO\Rule\Violation;
use RuntimeShield\DTO\Rule\ViolationCollection;

final class RouteProtectionRiskLabelTest extends TestCase
{
    #[Test]
    public function high_violation_gives_high_risk_label(): void
    {
        $this->assertSame('HIGH RISK', $this->protectionWith(Severity::HIGH)->riskLabel());
    }

    #[Test]
    public function medium_violation_gives_medium_risk_label(): void
    {
        $this->assertSame('MEDIUM RISK', $this->protectionWith(Severity::MEDIUM)->riskLabel());
    }

    #[Test]
    public function low_violation_gives_low_risk_label(): void
    {
        $this->assertSame('LOW RISK', $this->protectionWith(Severity::LOW)->riskLabel());
    }

    #[Test]
    public function info_violation_gives_info_label(): void
    {
        $this->assertSame('INFO', $this->protectionWith(Severity::INFO)->riskLabel());
    }

    #[Test]
    public function violation_count_matches_collection_size(): void
    {
        $protection = new RouteProtection(
            method: 'POST',
            uri: 'contact',
            name: '',
            hasAuth: false,
            hasCsrf: false,
            hasRateLimit: false,
            violations: new ViolationCollection([
                new Violation('a', 'T', 'D', Severity::HIGH),
                new Violation('b', 'T', 'D', Severity::MEDIUM),
            ]),
        );

        $this->assertSame(2, $protection->violationCount());
    }
    private function protectionWith(Severity $severity): RouteProtection
    {
        return new RouteProtection(
            method: 'GET',
            uri: 'test',
            name: '',
            hasAuth: false,
            hasCsrf: false,
            hasRateLimit: false,
            violations: new ViolationCollection([new Violation('r', 'T', 'D', $severity)]),
        );
    }
}
