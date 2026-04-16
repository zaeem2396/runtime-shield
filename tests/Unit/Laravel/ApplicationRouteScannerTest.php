<?php

declare(strict_types=1);

namespace RuntimeShield\Tests\Unit\Laravel;

use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\Test;
use RuntimeShield\DTO\Rule\ViolationCollection;
use RuntimeShield\Laravel\Providers\RuntimeShieldServiceProvider;
use RuntimeShield\Laravel\Support\ApplicationRouteScanner;

final class ApplicationRouteScannerTest extends TestCase
{
    #[Test]
    public function it_resolves_from_the_container(): void
    {
        $scanner = $this->app->make(ApplicationRouteScanner::class);

        $this->assertInstanceOf(ApplicationRouteScanner::class, $scanner);
    }

    #[Test]
    public function scan_routes_returns_a_violation_collection(): void
    {
        $scanner = $this->app->make(ApplicationRouteScanner::class);

        $violations = $scanner->scanRoutes();

        $this->assertInstanceOf(ViolationCollection::class, $violations);
    }

    #[Test]
    public function scannable_route_count_is_non_negative(): void
    {
        $scanner = $this->app->make(ApplicationRouteScanner::class);

        $this->assertGreaterThanOrEqual(0, $scanner->scannableRouteCount());
    }

    /** @return list<class-string> */
    protected function getPackageProviders($app): array
    {
        return [RuntimeShieldServiceProvider::class];
    }
}
