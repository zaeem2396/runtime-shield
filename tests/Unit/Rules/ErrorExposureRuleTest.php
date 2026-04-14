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
use RuntimeShield\Rules\ErrorExposureRule;

final class ErrorExposureRuleTest extends TestCase
{
    private ErrorExposureRule $rule;

    #[Test]
    public function it_has_correct_id_and_severity(): void
    {
        $this->assertSame('error-exposure-detected', $this->rule->id());
        $this->assertSame(Severity::HIGH, $this->rule->severity());
    }

    #[Test]
    public function it_fires_on_5xx_with_debug_headers(): void
    {
        $ctx = $this->makeContext(
            statusCode: 500,
            headers: ['X-Debug-Exception' => 'RuntimeException', 'Content-Type' => 'text/html'],
            bodySize: 1280,
        );

        $violations = $this->rule->evaluate($ctx);

        $this->assertCount(1, $violations);
        $this->assertSame('error-exposure-detected', $violations[0]->ruleId);
    }

    #[Test]
    public function it_does_not_fire_on_non_5xx_response(): void
    {
        $ctx = $this->makeContext(
            statusCode: 401,
            headers: ['X-Debug-Exception' => 'RuntimeException'],
            bodySize: 500,
        );

        $this->assertSame([], $this->rule->evaluate($ctx));
    }

    #[Test]
    public function it_does_not_fire_when_response_is_missing(): void
    {
        $ctx = (new RuntimeContextBuilder())
            ->withRoute(new RouteSignal('api.users', 'api/users', 'Closure', '', [], true))
            ->withRequest(new RequestSignal('GET', 'http://localhost/api/users', '/api/users', '127.0.0.1', [], [], 0, new \DateTimeImmutable()))
            ->build();

        $this->assertSame([], $this->rule->evaluate($ctx));
    }

    protected function setUp(): void
    {
        $this->rule = new ErrorExposureRule();
    }

    private function makeContext(int $statusCode, array $headers, int $bodySize): SecurityRuntimeContext
    {
        $route = new RouteSignal('api.users', 'api/users', 'Closure', '', ['api'], true);
        $request = new RequestSignal('GET', 'http://localhost/api/users', '/api/users', '127.0.0.1', [], [], 0, new \DateTimeImmutable());
        $response = new ResponseSignal($statusCode, 'Server Error', $headers, $bodySize, 42.0, new \DateTimeImmutable());

        return (new RuntimeContextBuilder())
            ->withRoute($route)
            ->withRequest($request)
            ->withResponse($response)
            ->build();
    }
}
