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
use RuntimeShield\Rules\MissingSecurityHeadersRule;

final class MissingSecurityHeadersRuleTest extends TestCase
{
    private MissingSecurityHeadersRule $rule;

    #[Test]
    public function it_has_correct_id_and_severity(): void
    {
        $this->assertSame('missing-security-headers', $this->rule->id());
        $this->assertSame(Severity::MEDIUM, $this->rule->severity());
    }

    #[Test]
    public function it_fires_when_required_headers_are_missing(): void
    {
        $ctx = $this->makeContext(
            url: 'https://example.test/login',
            headers: ['Content-Type' => 'text/html; charset=UTF-8'],
        );

        $violations = $this->rule->evaluate($ctx);

        $this->assertCount(1, $violations);
        $this->assertSame('missing-security-headers', $violations[0]->ruleId);
    }

    #[Test]
    public function it_does_not_fire_when_all_required_headers_exist(): void
    {
        $ctx = $this->makeContext(
            url: 'https://example.test/login',
            headers: [
                'Content-Security-Policy' => "default-src 'self'",
                'X-Frame-Options' => 'DENY',
                'Strict-Transport-Security' => 'max-age=63072000; includeSubDomains',
            ],
        );

        $this->assertSame([], $this->rule->evaluate($ctx));
    }

    #[Test]
    public function it_does_not_require_hsts_for_http_urls(): void
    {
        $ctx = $this->makeContext(
            url: 'http://example.test/login',
            headers: [
                'Content-Security-Policy' => "default-src 'self'",
                'X-Frame-Options' => 'DENY',
            ],
        );

        $this->assertSame([], $this->rule->evaluate($ctx));
    }

    protected function setUp(): void
    {
        $this->rule = new MissingSecurityHeadersRule();
    }

    private function makeContext(string $url, array $headers): SecurityRuntimeContext
    {
        $route = new RouteSignal('web.login', 'login', 'Closure', '', ['web'], true);
        $request = new RequestSignal('GET', $url, '/login', '127.0.0.1', [], [], 0, new \DateTimeImmutable());
        $response = new ResponseSignal(200, 'OK', $headers, 128, 10.0, new \DateTimeImmutable());

        return (new RuntimeContextBuilder())
            ->withRoute($route)
            ->withRequest($request)
            ->withResponse($response)
            ->build();
    }
}
