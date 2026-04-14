<?php

declare(strict_types=1);

namespace RuntimeShield\Tests\Unit\Rules;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeShield\Core\RuntimeContextBuilder;
use RuntimeShield\DTO\Rule\Severity;
use RuntimeShield\DTO\SecurityRuntimeContext;
use RuntimeShield\DTO\Signal\RequestSignal;
use RuntimeShield\DTO\Signal\ResponseSignal;
use RuntimeShield\DTO\Signal\RouteSignal;
use RuntimeShield\Rules\BruteForcePatternRule;

final class BruteForcePatternRuleTest extends TestCase
{
    private BruteForcePatternRule $rule;

    #[Test]
    public function it_has_correct_id_and_severity(): void
    {
        $this->assertSame('brute-force-pattern-detected', $this->rule->id());
        $this->assertSame(Severity::HIGH, $this->rule->severity());
    }

    #[Test]
    public function it_fires_on_401_auth_endpoint_without_throttle(): void
    {
        $ctx = $this->makeContext(
            uri: 'auth/login',
            middleware: ['web'],
            statusCode: 401,
        );

        $violations = $this->rule->evaluate($ctx);

        $this->assertCount(1, $violations);
        $this->assertSame('brute-force-pattern-detected', $violations[0]->ruleId);
    }

    #[Test]
    public function it_does_not_fire_when_rate_limited(): void
    {
        $ctx = $this->makeContext(
            uri: 'auth/login',
            middleware: ['throttle:5,1'],
            statusCode: 401,
        );

        $this->assertSame([], $this->rule->evaluate($ctx));
    }

    #[Test]
    public function it_does_not_fire_for_non_auth_routes(): void
    {
        $ctx = $this->makeContext(
            uri: 'products/list',
            middleware: ['web'],
            statusCode: 401,
        );

        $this->assertSame([], $this->rule->evaluate($ctx));
    }

    #[Test]
    public function it_does_not_fire_for_non_401_status(): void
    {
        $ctx = $this->makeContext(
            uri: 'auth/login',
            middleware: ['web'],
            statusCode: 200,
        );

        $this->assertSame([], $this->rule->evaluate($ctx));
    }

    protected function setUp(): void
    {
        $this->rule = new BruteForcePatternRule();
    }

    private function makeContext(string $uri, array $middleware, int $statusCode): SecurityRuntimeContext
    {
        $route = new RouteSignal('auth.login', $uri, 'Closure', '', $middleware, true);
        $request = new RequestSignal('POST', 'http://localhost/' . $uri, '/' . $uri, '127.0.0.1', [], [], 128, new \DateTimeImmutable());
        $response = new ResponseSignal($statusCode, '', ['Content-Type' => 'application/json'], 64, 35.0, new \DateTimeImmutable());

        return (new RuntimeContextBuilder())
            ->withRoute($route)
            ->withRequest($request)
            ->withResponse($response)
            ->build();
    }
}
