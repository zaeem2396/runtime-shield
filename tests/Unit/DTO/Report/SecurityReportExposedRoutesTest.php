<?php

declare(strict_types=1);

namespace RuntimeShield\Tests\Unit\DTO\Report;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeShield\DTO\Report\RouteProtection;
use RuntimeShield\DTO\Report\SecurityReport;
use RuntimeShield\DTO\Rule\Severity;
use RuntimeShield\DTO\Rule\Violation;
use RuntimeShield\DTO\Rule\ViolationCollection;

final class SecurityReportExposedRoutesTest extends TestCase
{
    #[Test]
    public function exposed_route_count_is_zero_when_all_routes_are_safe(): void
    {
        $report = new SecurityReport(
            scannedAt: new \DateTimeImmutable(),
            routeCount: 2,
            violations: new ViolationCollection(),
            routeProtections: [$this->makeProtection(false), $this->makeProtection(false)],
        );

        $this->assertSame(0, $report->exposedRouteCount());
    }

    #[Test]
    public function exposed_route_count_reflects_routes_with_violations(): void
    {
        $report = new SecurityReport(
            scannedAt: new \DateTimeImmutable(),
            routeCount: 3,
            violations: new ViolationCollection(),
            routeProtections: [
                $this->makeProtection(false),
                $this->makeProtection(true),
                $this->makeProtection(true),
            ],
        );

        $this->assertSame(2, $report->exposedRouteCount());
    }

    #[Test]
    public function route_count_defaults_to_zero_with_empty_protections(): void
    {
        $report = new SecurityReport(
            scannedAt: new \DateTimeImmutable(),
            routeCount: 5,
            violations: new ViolationCollection(),
        );

        $this->assertSame(0, $report->exposedRouteCount());
    }
    private function makeProtection(bool $withViolation = false): RouteProtection
    {
        $violations = $withViolation
            ? new ViolationCollection([new Violation('r', 'T', 'D', Severity::HIGH)])
            : new ViolationCollection();

        return new RouteProtection('GET', 'test', '', true, true, true, $violations);
    }
}
