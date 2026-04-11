<?php

declare(strict_types=1);

namespace RuntimeShield\Tests\Unit\Core\Report;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeShield\Contracts\Rule\RuleEngineContract;
use RuntimeShield\Core\Report\ReportBuilder;
use RuntimeShield\Core\Report\RouteProtectionAnalyzer;
use RuntimeShield\DTO\Report\SecurityReport;
use RuntimeShield\DTO\Rule\ViolationCollection;

final class ReportBuilderTest extends TestCase
{
    #[Test]
    public function build_returns_security_report_with_zero_routes_when_router_has_no_routes(): void
    {
        $router = $this->createMock(\Illuminate\Routing\Router::class);

        $routeCollection = $this->createMock(\Illuminate\Routing\RouteCollectionInterface::class);
        $routeCollection->method('getRoutes')->willReturn([]);

        $router->method('getRoutes')->willReturn($routeCollection);

        $engine = $this->createMock(RuleEngineContract::class);
        $engine->method('run')->willReturn(new ViolationCollection());

        $builder = new ReportBuilder($router, $engine, new RouteProtectionAnalyzer());
        $report = $builder->build();

        $this->assertInstanceOf(SecurityReport::class, $report);
        $this->assertSame(0, $report->routeCount);
        $this->assertTrue($report->violations->isEmpty());
    }

    #[Test]
    public function build_report_has_correct_scanned_at_timestamp(): void
    {
        $router = $this->createMock(\Illuminate\Routing\Router::class);

        $routeCollection = $this->createMock(\Illuminate\Routing\RouteCollectionInterface::class);
        $routeCollection->method('getRoutes')->willReturn([]);

        $router->method('getRoutes')->willReturn($routeCollection);

        $engine = $this->createMock(RuleEngineContract::class);
        $engine->method('run')->willReturn(new ViolationCollection());

        $before = new \DateTimeImmutable();
        $report = (new ReportBuilder($router, $engine, new RouteProtectionAnalyzer()))->build();
        $after = new \DateTimeImmutable();

        $this->assertGreaterThanOrEqual($before->getTimestamp(), $report->scannedAt->getTimestamp());
        $this->assertLessThanOrEqual($after->getTimestamp(), $report->scannedAt->getTimestamp());
    }

    #[Test]
    public function build_returns_empty_violation_collection_when_no_rules_fire(): void
    {
        $router = $this->createMock(\Illuminate\Routing\Router::class);

        $routeCollection = $this->createMock(\Illuminate\Routing\RouteCollectionInterface::class);
        $routeCollection->method('getRoutes')->willReturn([]);

        $router->method('getRoutes')->willReturn($routeCollection);

        $engine = $this->createMock(RuleEngineContract::class);
        $engine->method('run')->willReturn(new ViolationCollection());

        $report = (new ReportBuilder($router, $engine, new RouteProtectionAnalyzer()))->build();

        $this->assertSame(0, $report->violations->count());
    }
}
