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
use RuntimeShield\Rules\ResponseAnomalyRule;

final class ResponseAnomalyRuleTest extends TestCase
{
    private ResponseAnomalyRule $rule;

    #[Test]
    public function it_has_correct_id_and_severity(): void
    {
        $this->assertSame('response-anomaly-detected', $this->rule->id());
        $this->assertSame(Severity::MEDIUM, $this->rule->severity());
    }

    #[Test]
    public function it_fires_on_slow_response(): void
    {
        $ctx = $this->makeContext(statusCode: 200, bodySize: 200, responseTimeMs: 6_200.0, method: 'GET');
        $violations = $this->rule->evaluate($ctx);

        $this->assertCount(1, $violations);
        $this->assertSame('response-anomaly-detected', $violations[0]->ruleId);
    }

    #[Test]
    public function it_fires_on_large_body(): void
    {
        $ctx = $this->makeContext(statusCode: 200, bodySize: 2_500_000, responseTimeMs: 90.0, method: 'GET');
        $violations = $this->rule->evaluate($ctx);

        $this->assertCount(1, $violations);
    }

    #[Test]
    public function it_does_not_fire_for_normal_response(): void
    {
        $ctx = $this->makeContext(statusCode: 200, bodySize: 2000, responseTimeMs: 120.0, method: 'GET');
        $this->assertSame([], $this->rule->evaluate($ctx));
    }

    #[Test]
    public function it_fires_on_5xx_empty_body(): void
    {
        $ctx = $this->makeContext(statusCode: 500, bodySize: 0, responseTimeMs: 300.0, method: 'GET');
        $violations = $this->rule->evaluate($ctx);

        $this->assertCount(1, $violations);
    }

    protected function setUp(): void
    {
        $this->rule = new ResponseAnomalyRule();
    }

    private function makeContext(int $statusCode, int $bodySize, float $responseTimeMs, string $method): SecurityRuntimeContext
    {
        $route = new RouteSignal('api.health', 'api/health', 'Closure', '', ['api'], true);
        $request = new RequestSignal($method, 'http://localhost/api/health', '/api/health', '127.0.0.1', [], [], 0, new \DateTimeImmutable());
        $response = new ResponseSignal($statusCode, '', ['Content-Type' => 'application/json'], $bodySize, $responseTimeMs, new \DateTimeImmutable());

        return (new RuntimeContextBuilder())
            ->withRoute($route)
            ->withRequest($request)
            ->withResponse($response)
            ->build();
    }
}
