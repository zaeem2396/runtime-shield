<?php

declare(strict_types=1);

namespace RuntimeShield\Tests\Unit\Core\Report;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeShield\Core\Report\RouteProtectionAnalyzer;
use RuntimeShield\DTO\Signal\RouteSignal;

final class RouteProtectionAnalyzerApiTest extends TestCase
{
    private RouteProtectionAnalyzer $analyzer;

    protected function setUp(): void
    {
        $this->analyzer = new RouteProtectionAnalyzer();
    }

    private function makeRoute(string $uri, array $middleware): RouteSignal
    {
        return new RouteSignal('', $uri, 'Closure', '', $middleware, false);
    }

    #[Test]
    public function csrf_not_required_for_uri_starting_with_api(): void
    {
        $route = $this->makeRoute('api/users', []);

        $this->assertTrue($this->analyzer->hasCsrf($route, 'POST'));
    }

    #[Test]
    public function csrf_not_required_for_delete_method_with_api_middleware(): void
    {
        $route = $this->makeRoute('users/1', ['api']);

        $this->assertTrue($this->analyzer->hasCsrf($route, 'DELETE'));
    }

    #[Test]
    public function rate_limit_detected_for_plain_throttle(): void
    {
        $route = $this->makeRoute('login', ['throttle']);

        $this->assertTrue($this->analyzer->hasRateLimit($route));
    }

    #[Test]
    public function can_middleware_prefix_counts_as_auth(): void
    {
        $route = $this->makeRoute('admin', ['can:manage-users']);

        $this->assertTrue($this->analyzer->hasAuth($route));
    }

    #[Test]
    public function no_middleware_means_no_protections(): void
    {
        $route = $this->makeRoute('public-page', []);

        $this->assertFalse($this->analyzer->hasAuth($route));
        $this->assertFalse($this->analyzer->hasRateLimit($route));
    }
}
