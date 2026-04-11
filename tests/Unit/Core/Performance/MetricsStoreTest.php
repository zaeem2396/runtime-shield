<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Performance;

use PHPUnit\Framework\TestCase;
use RuntimeShield\Core\Performance\MetricsStore;
use RuntimeShield\DTO\Performance\MiddlewareMetrics;

final class MetricsStoreTest extends TestCase
{
    public function test_empty_store_count_is_zero(): void
    {
        $store = new MetricsStore();
        $this->assertSame(0, $store->count());
    }

    public function test_push_adds_record(): void
    {
        $store = new MetricsStore();
        $store->push($this->makeMetrics(1.0));
        $this->assertSame(1, $store->count());
    }

    public function test_all_returns_all_records(): void
    {
        $store = new MetricsStore();
        $store->push($this->makeMetrics(1.0));
        $store->push($this->makeMetrics(2.0));
        $this->assertCount(2, $store->all());
    }

    public function test_average_ms_returns_0_when_empty(): void
    {
        $this->assertSame(0.0, (new MetricsStore())->averageMs());
    }

    public function test_average_ms_is_correct(): void
    {
        $store = new MetricsStore();
        $store->push($this->makeMetrics(2.0));
        $store->push($this->makeMetrics(4.0));
        $this->assertSame(3.0, $store->averageMs());
    }

    public function test_max_ms_returns_0_when_empty(): void
    {
        $this->assertSame(0.0, (new MetricsStore())->maxMs());
    }

    public function test_max_ms_returns_highest(): void
    {
        $store = new MetricsStore();
        $store->push($this->makeMetrics(1.0));
        $store->push($this->makeMetrics(5.0));
        $store->push($this->makeMetrics(3.0));
        $this->assertSame(5.0, $store->maxMs());
    }

    public function test_min_ms_returns_0_when_empty(): void
    {
        $this->assertSame(0.0, (new MetricsStore())->minMs());
    }

    public function test_min_ms_returns_lowest(): void
    {
        $store = new MetricsStore();
        $store->push($this->makeMetrics(4.0));
        $store->push($this->makeMetrics(1.0));
        $store->push($this->makeMetrics(3.0));
        $this->assertSame(1.0, $store->minMs());
    }

    public function test_capacity_evicts_oldest_record(): void
    {
        $store = new MetricsStore(capacity: 2);
        $store->push($this->makeMetrics(1.0));
        $store->push($this->makeMetrics(2.0));
        $store->push($this->makeMetrics(3.0)); // evicts 1.0

        $this->assertSame(2, $store->count());
        $this->assertSame(2.0, $store->minMs());
    }

    public function test_flush_clears_all_records(): void
    {
        $store = new MetricsStore();
        $store->push($this->makeMetrics(1.0));
        $store->flush();
        $this->assertSame(0, $store->count());
    }

    public function test_sampling_rate_0_when_empty(): void
    {
        $this->assertSame(0.0, (new MetricsStore())->samplingRate());
    }

    public function test_sampling_rate_1_when_all_sampled(): void
    {
        $store = new MetricsStore();
        $store->push($this->makeMetrics(1.0, true));
        $store->push($this->makeMetrics(2.0, true));
        $this->assertSame(1.0, $store->samplingRate());
    }

    public function test_sampling_rate_0_when_none_sampled(): void
    {
        $store = new MetricsStore();
        $store->push($this->makeMetrics(1.0, false));
        $this->assertSame(0.0, $store->samplingRate());
    }

    public function test_to_array_contains_expected_keys(): void
    {
        $store = new MetricsStore();
        $store->push($this->makeMetrics(1.0));
        $arr = $store->toArray();

        $this->assertArrayHasKey('count', $arr);
        $this->assertArrayHasKey('avg_ms', $arr);
        $this->assertArrayHasKey('max_ms', $arr);
        $this->assertArrayHasKey('min_ms', $arr);
        $this->assertArrayHasKey('sampling_rate', $arr);
    }
    private function makeMetrics(float $processingMs, bool $wasSampled = true): MiddlewareMetrics
    {
        return new MiddlewareMetrics($processingMs, 0, $wasSampled, 0, new \DateTimeImmutable());
    }
}
