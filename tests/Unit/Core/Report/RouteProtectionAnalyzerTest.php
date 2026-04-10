<?php

declare(strict_types=1);

namespace RuntimeShield\Tests\Unit\Core\Report;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeShield\Core\Report\RouteProtectionAnalyzer;
use RuntimeShield\DTO\Signal\RouteSignal;

final class RouteProtectionAnalyzerTest extends TestCase
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
    public function has_auth_returns_true_for_auth_middleware(): void
    {
        $route = $this->makeRoute('dashboard', ['auth']);

        $this->assertTrue($this->analyzer->hasAuth($route));
    }

    #[Test]
    public function has_auth_returns_true_for_auth_with_guard(): void
    {
        $this->assertTrue($this->analyzer->hasAuth($this->makeRoute('dashboard', ['auth:sanctum'])));
    }

    #[Test]
    public function has_auth_returns_false_without_auth_middleware(): void
    {
        $this->assertFalse($this->analyzer->hasAuth($this->makeRoute('dashboard', ['throttle'])));
    }

    #[Test]
    public function has_csrf_returns_true_for_web_middleware_on_post(): void
    {
        $route = $this->makeRoute('contact', ['web']);

        $this->assertTrue($this->analyzer->hasCsrf($route, 'POST'));
    }

    #[Test]
    public function has_csrf_returns_true_for_get_request_always(): void
    {
        $route = $this->makeRoute('contact', []);

        $this->assertTrue($this->analyzer->hasCsrf($route, 'GET'));
    }

    #[Test]
    public function has_csrf_returns_false_for_post_without_web_middleware(): void
    {
        $route = $this->makeRoute('contact', ['auth']);

        $this->assertFalse($this->analyzer->hasCsrf($route, 'POST'));
    }

    #[Test]
    public function has_csrf_returns_true_for_api_routes(): void
    {
        $route = $this->makeRoute('api/users', ['api']);

        $this->assertTrue($this->analyzer->hasCsrf($route, 'POST'));
    }

    #[Test]
    public function has_rate_limit_returns_true_for_throttle_middleware(): void
    {
        $route = $this->makeRoute('api/login', ['throttle:10,1']);

        $this->assertTrue($this->analyzer->hasRateLimit($route));
    }

    #[Test]
    public function has_rate_limit_returns_false_without_throttle_middleware(): void
    {
        $route = $this->makeRoute('api/login', ['auth']);

        $this->assertFalse($this->analyzer->hasRateLimit($route));
    }
}
