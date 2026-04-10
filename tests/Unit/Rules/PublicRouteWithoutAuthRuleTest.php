<?php

declare(strict_types=1);

namespace RuntimeShield\Tests\Unit\Rules;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeShield\Core\RuntimeContextBuilder;
use RuntimeShield\DTO\Rule\Severity;
use RuntimeShield\DTO\SecurityRuntimeContext;
use RuntimeShield\DTO\Signal\RequestSignal;
use RuntimeShield\DTO\Signal\RouteSignal;
use RuntimeShield\Rules\PublicRouteWithoutAuthRule;

final class PublicRouteWithoutAuthRuleTest extends TestCase
{
    private PublicRouteWithoutAuthRule $rule;

    protected function setUp(): void
    {
        $this->rule = new PublicRouteWithoutAuthRule();
    }

    private function makeContext(array $middleware, string $method = 'GET'): SecurityRuntimeContext
    {
        $route = new RouteSignal('', 'dashboard', 'Closure', '', $middleware, false);
        $request = new RequestSignal($method, 'http://localhost/dashboard', '/dashboard', '127.0.0.1', [], [], 0, new DateTimeImmutable());

        return (new RuntimeContextBuilder())->withRoute($route)->withRequest($request)->build();
    }

    #[Test]
    public function it_has_correct_id_and_severity(): void
    {
        $this->assertSame('public-route-without-auth', $this->rule->id());
        $this->assertSame(Severity::CRITICAL, $this->rule->severity());
    }

    #[Test]
    public function it_fires_when_no_auth_middleware_present(): void
    {
        $violations = $this->rule->evaluate($this->makeContext([]));

        $this->assertCount(1, $violations);
        $this->assertSame(Severity::CRITICAL, $violations[0]->severity);
    }

    #[Test]
    public function it_does_not_fire_when_auth_middleware_present(): void
    {
        $violations = $this->rule->evaluate($this->makeContext(['auth']));

        $this->assertCount(0, $violations);
    }

    #[Test]
    public function it_does_not_fire_for_auth_with_guard_parameter(): void
    {
        $violations = $this->rule->evaluate($this->makeContext(['auth:sanctum']));

        $this->assertCount(0, $violations);
    }

    #[Test]
    public function it_does_not_fire_for_can_permission_middleware(): void
    {
        $violations = $this->rule->evaluate($this->makeContext(['can:view-dashboard']));

        $this->assertCount(0, $violations);
    }

    #[Test]
    public function it_returns_empty_when_route_signal_is_null(): void
    {
        $context = (new RuntimeContextBuilder())->build();

        $this->assertCount(0, $this->rule->evaluate($context));
    }

    #[Test]
    public function violation_includes_route_uri(): void
    {
        $violations = $this->rule->evaluate($this->makeContext([]));

        $this->assertStringContainsString('dashboard', $violations[0]->route);
    }
}
