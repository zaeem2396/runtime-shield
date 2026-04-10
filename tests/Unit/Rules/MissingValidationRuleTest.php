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
use RuntimeShield\Rules\MissingValidationRule;

final class MissingValidationRuleTest extends TestCase
{
    private MissingValidationRule $rule;

    #[Test]
    public function it_has_correct_id_and_severity(): void
    {
        $this->assertSame('missing-validation', $this->rule->id());
        $this->assertSame(Severity::LOW, $this->rule->severity());
    }

    #[Test]
    public function it_fires_for_post_without_validation_middleware(): void
    {
        $violations = $this->rule->evaluate($this->makeContext('POST'));

        $this->assertCount(1, $violations);
        $this->assertSame(Severity::LOW, $violations[0]->severity);
    }

    #[Test]
    public function it_fires_for_put_and_patch(): void
    {
        $this->assertCount(1, $this->rule->evaluate($this->makeContext('PUT')));
        $this->assertCount(1, $this->rule->evaluate($this->makeContext('PATCH')));
    }

    #[Test]
    public function it_does_not_fire_for_get_request(): void
    {
        $violations = $this->rule->evaluate($this->makeContext('GET'));

        $this->assertCount(0, $violations);
    }

    #[Test]
    public function it_does_not_fire_when_validate_middleware_present(): void
    {
        $violations = $this->rule->evaluate($this->makeContext('POST', ['validate']));

        $this->assertCount(0, $violations);
    }

    #[Test]
    public function it_returns_empty_when_route_or_request_signal_is_null(): void
    {
        $context = (new RuntimeContextBuilder())->build();

        $this->assertCount(0, $this->rule->evaluate($context));
    }

    protected function setUp(): void
    {
        $this->rule = new MissingValidationRule();
    }

    private function makeContext(string $method, array $middleware = []): SecurityRuntimeContext
    {
        $route = new RouteSignal('', 'users', 'Closure', '', $middleware, false);
        $request = new RequestSignal($method, 'http://localhost/users', '/users', '127.0.0.1', [], [], 0, new \DateTimeImmutable());

        return (new RuntimeContextBuilder())->withRoute($route)->withRequest($request)->build();
    }
}
