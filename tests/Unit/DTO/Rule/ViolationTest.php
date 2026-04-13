<?php

declare(strict_types=1);

namespace RuntimeShield\Tests\Unit\DTO\Rule;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeShield\DTO\Rule\Severity;
use RuntimeShield\DTO\Rule\Violation;
use RuntimeShield\DTO\Rule\ViolationAdvisory;

final class ViolationTest extends TestCase
{
    #[Test]
    public function it_stores_all_fields(): void
    {
        $v = $this->makeViolation();

        $this->assertSame('missing-rate-limit', $v->ruleId);
        $this->assertSame('Missing Rate Limit', $v->title);
        $this->assertSame('No throttle middleware on /api/users.', $v->description);
        $this->assertSame(Severity::MEDIUM, $v->severity);
        $this->assertSame('/api/users', $v->route);
        $this->assertSame(['middleware' => []], $v->context);
    }

    #[Test]
    public function it_defaults_route_and_context_to_empty(): void
    {
        $v = new Violation(
            ruleId: 'rule-id',
            title: 'Rule Title',
            description: 'Some description.',
            severity: Severity::INFO,
        );

        $this->assertSame('', $v->route);
        $this->assertSame([], $v->context);
    }

    #[Test]
    public function to_array_includes_all_keys(): void
    {
        $arr = $this->makeViolation()->toArray();

        $this->assertArrayHasKey('rule_id', $arr);
        $this->assertArrayHasKey('title', $arr);
        $this->assertArrayHasKey('description', $arr);
        $this->assertArrayHasKey('severity', $arr);
        $this->assertArrayHasKey('route', $arr);
        $this->assertArrayHasKey('context', $arr);
    }

    #[Test]
    public function to_array_serializes_severity_as_string(): void
    {
        $arr = $this->makeViolation()->toArray();

        $this->assertSame('medium', $arr['severity']);
    }

    #[Test]
    public function violation_is_immutable(): void
    {
        $v = $this->makeViolation();

        $this->assertSame('missing-rate-limit', $v->ruleId);
    }

    #[Test]
    public function to_array_includes_advisory_when_set(): void
    {
        $adv = new ViolationAdvisory(
            summary: 'S',
            impact: 'I',
            remediation: 'R',
            advisorySeverity: Severity::HIGH,
            confidence: 0.5,
            rationale: 'Because',
        );
        $v = $this->makeViolation()->withAdvisory($adv);
        $arr = $v->toArray();

        $this->assertArrayHasKey('advisory', $arr);
        $this->assertSame('S', $arr['advisory']['summary']);
        $this->assertSame('high', $arr['advisory']['severity']);
        $this->assertSame(0.5, $arr['advisory']['confidence']);
    }

    #[Test]
    public function to_array_omits_advisory_when_null(): void
    {
        $arr = $this->makeViolation()->toArray();

        $this->assertArrayNotHasKey('advisory', $arr);
    }
    private function makeViolation(): Violation
    {
        return new Violation(
            ruleId: 'missing-rate-limit',
            title: 'Missing Rate Limit',
            description: 'No throttle middleware on /api/users.',
            severity: Severity::MEDIUM,
            route: '/api/users',
            context: ['middleware' => []],
        );
    }
}
