<?php

declare(strict_types=1);

namespace Tests\Unit\DTO\Performance;

use PHPUnit\Framework\TestCase;
use RuntimeShield\DTO\Performance\MiddlewareMetrics;

final class MiddlewareMetricsTest extends TestCase
{
    public function test_properties_are_stored(): void
    {
        $m = $this->make(3.5, 20, false, 3);

        $this->assertSame(3.5, $m->processingMs);
        $this->assertSame(20, $m->memoryDeltaKb);
        $this->assertFalse($m->wasSampled);
        $this->assertSame(3, $m->rulesEvaluated);
    }

    public function test_is_within_budget_true_when_under_default_5ms(): void
    {
        $this->assertTrue($this->make(4.9)->isWithinBudget());
    }

    public function test_is_within_budget_true_when_exactly_at_budget(): void
    {
        $this->assertTrue($this->make(5.0)->isWithinBudget(5.0));
    }

    public function test_is_within_budget_false_when_over_budget(): void
    {
        $this->assertFalse($this->make(5.1)->isWithinBudget(5.0));
    }

    public function test_is_within_budget_custom_threshold(): void
    {
        $this->assertTrue($this->make(1.0)->isWithinBudget(2.0));
        $this->assertFalse($this->make(3.0)->isWithinBudget(2.0));
    }

    public function test_formatted_ms_contains_ms_suffix(): void
    {
        $this->assertStringContainsString('ms', $this->make(1.2345)->formattedMs());
    }

    public function test_to_array_contains_expected_keys(): void
    {
        $arr = $this->make()->toArray();

        $this->assertArrayHasKey('processing_ms', $arr);
        $this->assertArrayHasKey('memory_delta_kb', $arr);
        $this->assertArrayHasKey('was_sampled', $arr);
        $this->assertArrayHasKey('rules_evaluated', $arr);
        $this->assertArrayHasKey('captured_at', $arr);
    }

    public function test_to_array_values_are_correct(): void
    {
        $arr = $this->make(2.5, 10, true, 5)->toArray();

        $this->assertSame(2.5, $arr['processing_ms']);
        $this->assertSame(10, $arr['memory_delta_kb']);
        $this->assertTrue($arr['was_sampled']);
        $this->assertSame(5, $arr['rules_evaluated']);
        $this->assertStringContainsString('2026-04-11', $arr['captured_at']);
    }
    private function make(
        float $processingMs = 2.5,
        int $memoryDeltaKb = 10,
        bool $wasSampled = true,
        int $rulesEvaluated = 5,
    ): MiddlewareMetrics {
        return new MiddlewareMetrics(
            processingMs: $processingMs,
            memoryDeltaKb: $memoryDeltaKb,
            wasSampled: $wasSampled,
            rulesEvaluated: $rulesEvaluated,
            capturedAt: new \DateTimeImmutable('2026-04-11T00:00:00+00:00'),
        );
    }
}
