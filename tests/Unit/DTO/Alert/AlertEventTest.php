<?php

declare(strict_types=1);

namespace Tests\Unit\DTO\Alert;

use PHPUnit\Framework\TestCase;
use RuntimeShield\DTO\Alert\AlertEvent;
use RuntimeShield\DTO\Rule\Severity;
use RuntimeShield\DTO\Rule\Violation;
use RuntimeShield\DTO\Rule\ViolationCollection;

final class AlertEventTest extends TestCase
{
    public function test_properties_are_stored(): void
    {
        $violations = new ViolationCollection([$this->makeViolation()]);
        $at = new \DateTimeImmutable('2026-04-11T10:00:00+00:00');
        $event = new AlertEvent($violations, 'dashboard', $at);

        $this->assertSame($violations, $event->violations);
        $this->assertSame('dashboard', $event->route);
        $this->assertSame($at, $event->triggeredAt);
    }

    public function test_summary_singular(): void
    {
        $event = $this->makeEvent(1, 'home');
        $this->assertStringContainsString('1 violation', $event->summary());
        $this->assertStringContainsString('[home]', $event->summary());
    }

    public function test_summary_plural(): void
    {
        $event = $this->makeEvent(3, 'api/users');
        $this->assertStringContainsString('3 violations', $event->summary());
    }

    public function test_summary_unknown_route_when_empty(): void
    {
        $event = $this->makeEvent(1, '');
        $this->assertStringContainsString('unknown', $event->summary());
    }

    public function test_highest_severity_returns_null_for_empty(): void
    {
        $event = new AlertEvent(new ViolationCollection(), 'route', new \DateTimeImmutable());
        $this->assertNull($event->highestSeverityViolation());
    }

    public function test_highest_severity_returns_most_critical(): void
    {
        $violations = new ViolationCollection([
            $this->makeViolation('r1', Severity::LOW),
            $this->makeViolation('r2', Severity::CRITICAL),
            $this->makeViolation('r3', Severity::HIGH),
        ]);

        $event = new AlertEvent($violations, 'route', new \DateTimeImmutable());
        $top = $event->highestSeverityViolation();

        $this->assertNotNull($top);
        $this->assertSame(Severity::CRITICAL, $top->severity);
    }

    public function test_to_array_contains_expected_keys(): void
    {
        $event = $this->makeEvent(2, 'api');
        $arr = $event->toArray();

        $this->assertArrayHasKey('summary', $arr);
        $this->assertArrayHasKey('route', $arr);
        $this->assertArrayHasKey('triggered_at', $arr);
        $this->assertArrayHasKey('violation_count', $arr);
        $this->assertArrayHasKey('violations', $arr);
    }

    public function test_to_array_violation_count_matches(): void
    {
        $event = $this->makeEvent(3, 'home');
        $this->assertSame(3, $event->toArray()['violation_count']);
    }

    private function makeViolation(string $ruleId = 'test-rule', Severity $severity = Severity::HIGH): Violation
    {
        return new Violation($ruleId, 'Test', 'Description', $severity, 'route');
    }

    private function makeEvent(int $count, string $route): AlertEvent
    {
        $violations = new ViolationCollection(
            array_map(
                fn (int $i): Violation => $this->makeViolation("rule-{$i}"),
                range(1, $count),
            ),
        );

        return new AlertEvent($violations, $route, new \DateTimeImmutable());
    }
}
