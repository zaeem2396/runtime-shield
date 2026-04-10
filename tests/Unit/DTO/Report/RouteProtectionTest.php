<?php

declare(strict_types=1);

namespace RuntimeShield\Tests\Unit\DTO\Report;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeShield\DTO\Report\RouteProtection;
use RuntimeShield\DTO\Rule\Severity;
use RuntimeShield\DTO\Rule\Violation;
use RuntimeShield\DTO\Rule\ViolationCollection;

final class RouteProtectionTest extends TestCase
{
    private function makeProtection(
        bool $hasAuth = true,
        bool $hasCsrf = true,
        bool $hasRateLimit = true,
        ViolationCollection|null $violations = null,
    ): RouteProtection {
        return new RouteProtection(
            method: 'GET',
            uri: 'dashboard',
            name: 'dashboard',
            hasAuth: $hasAuth,
            hasCsrf: $hasCsrf,
            hasRateLimit: $hasRateLimit,
            violations: $violations ?? new ViolationCollection(),
        );
    }

    #[Test]
    public function it_stores_all_fields(): void
    {
        $p = $this->makeProtection();

        $this->assertSame('GET', $p->method);
        $this->assertSame('dashboard', $p->uri);
        $this->assertSame('dashboard', $p->name);
        $this->assertTrue($p->hasAuth);
        $this->assertTrue($p->hasCsrf);
        $this->assertTrue($p->hasRateLimit);
    }

    #[Test]
    public function is_fully_protected_when_no_violations(): void
    {
        $this->assertTrue($this->makeProtection()->isFullyProtected());
    }

    #[Test]
    public function is_not_fully_protected_when_violations_exist(): void
    {
        $violation  = new Violation('rule-id', 'Title', 'Desc', Severity::HIGH);
        $collection = new ViolationCollection([$violation]);

        $this->assertFalse($this->makeProtection(violations: $collection)->isFullyProtected());
    }

    #[Test]
    public function risk_label_is_safe_with_no_violations(): void
    {
        $this->assertSame('SAFE', $this->makeProtection()->riskLabel());
    }

    #[Test]
    public function risk_label_reflects_highest_severity(): void
    {
        $v = new Violation('rule-id', 'T', 'D', Severity::CRITICAL);
        $p = $this->makeProtection(violations: new ViolationCollection([$v]));

        $this->assertSame('CRITICAL', $p->riskLabel());
    }

    #[Test]
    public function highest_severity_returns_null_when_no_violations(): void
    {
        $this->assertNull($this->makeProtection()->highestSeverity());
    }

    #[Test]
    public function highest_severity_returns_worst_severity(): void
    {
        $vHigh = new Violation('a', 'T', 'D', Severity::HIGH);
        $vMed  = new Violation('b', 'T', 'D', Severity::MEDIUM);
        $p     = $this->makeProtection(violations: new ViolationCollection([$vMed, $vHigh]));

        $this->assertSame(Severity::HIGH, $p->highestSeverity());
    }
}
