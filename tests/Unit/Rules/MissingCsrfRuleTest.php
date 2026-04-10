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
use RuntimeShield\Rules\MissingCsrfRule;

final class MissingCsrfRuleTest extends TestCase
{
    private MissingCsrfRule $rule;

    #[Test]
    public function it_has_correct_id_and_severity(): void
    {
        $this->assertSame('missing-csrf-protection', $this->rule->id());
        $this->assertSame(Severity::HIGH, $this->rule->severity());
    }

    #[Test]
    public function it_fires_for_post_route_without_web_middleware(): void
    {
        $violations = $this->rule->evaluate($this->makeContext('POST', []));

        $this->assertCount(1, $violations);
        $this->assertSame(Severity::HIGH, $violations[0]->severity);
    }

    #[Test]
    public function it_does_not_fire_for_get_request(): void
    {
        $violations = $this->rule->evaluate($this->makeContext('GET', []));

        $this->assertCount(0, $violations);
    }

    #[Test]
    public function it_does_not_fire_when_web_middleware_present(): void
    {
        $violations = $this->rule->evaluate($this->makeContext('POST', ['web']));

        $this->assertCount(0, $violations);
    }

    #[Test]
    public function it_does_not_fire_for_api_routes(): void
    {
        $violations = $this->rule->evaluate($this->makeContext('POST', ['api'], 'api/users'));

        $this->assertCount(0, $violations);
    }

    #[Test]
    public function it_fires_for_put_and_patch_without_csrf(): void
    {
        $this->assertCount(1, $this->rule->evaluate($this->makeContext('PUT', [])));
        $this->assertCount(1, $this->rule->evaluate($this->makeContext('PATCH', [])));
    }

    #[Test]
    public function it_returns_empty_when_route_signal_is_null(): void
    {
        $context = (new RuntimeContextBuilder())->build();

        $this->assertCount(0, $this->rule->evaluate($context));
    }

    protected function setUp(): void
    {
        $this->rule = new MissingCsrfRule();
    }

    private function makeContext(string $method, array $middleware, string $uri = 'contact'): SecurityRuntimeContext
    {
        $route = new RouteSignal('', $uri, 'Closure', '', $middleware, false);
        $request = new RequestSignal($method, "http://localhost/{$uri}", "/{$uri}", '127.0.0.1', [], [], 0, new \DateTimeImmutable());

        return (new RuntimeContextBuilder())->withRoute($route)->withRequest($request)->build();
    }
}
