<?php

declare(strict_types=1);

namespace RuntimeShield\Tests\Unit\Rules;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeShield\Core\RuntimeContextBuilder;
use RuntimeShield\DTO\Rule\Severity;
use RuntimeShield\DTO\SecurityRuntimeContext;
use RuntimeShield\DTO\Signal\RequestSignal;
use RuntimeShield\DTO\Signal\RouteSignal;
use RuntimeShield\Rules\MissingRateLimitRule;

final class MissingRateLimitRuleTest extends TestCase
{
    private MissingRateLimitRule $rule;

    #[Test]
    public function it_has_correct_id_and_severity(): void
    {
        $this->assertSame('missing-rate-limit', $this->rule->id());
        $this->assertSame(Severity::MEDIUM, $this->rule->severity());
    }

    #[Test]
    public function it_fires_when_no_throttle_middleware_present(): void
    {
        $violations = $this->rule->evaluate($this->makeContext([]));

        $this->assertCount(1, $violations);
        $this->assertSame(Severity::MEDIUM, $violations[0]->severity);
    }

    #[Test]
    public function it_does_not_fire_when_throttle_middleware_present(): void
    {
        $violations = $this->rule->evaluate($this->makeContext(['throttle:60,1']));

        $this->assertCount(0, $violations);
    }

    #[Test]
    public function it_does_not_fire_for_plain_throttle(): void
    {
        $violations = $this->rule->evaluate($this->makeContext(['throttle']));

        $this->assertCount(0, $violations);
    }

    #[Test]
    public function it_returns_empty_when_route_signal_is_null(): void
    {
        $context = (new RuntimeContextBuilder())->build();

        $this->assertCount(0, $this->rule->evaluate($context));
    }

    #[Test]
    public function violation_references_route_uri(): void
    {
        $violations = $this->rule->evaluate($this->makeContext([]));

        $this->assertStringContainsString('api/users', $violations[0]->description);
    }

    protected function setUp(): void
    {
        $this->rule = new MissingRateLimitRule();
    }

    private function makeContext(array $middleware): SecurityRuntimeContext
    {
        $route = new RouteSignal('', 'api/users', 'Closure', '', $middleware, false);
        $request = new RequestSignal('GET', 'http://localhost/api/users', '/api/users', '127.0.0.1', [], [], 0, new \DateTimeImmutable());

        return (new RuntimeContextBuilder())->withRoute($route)->withRequest($request)->build();
    }
}
