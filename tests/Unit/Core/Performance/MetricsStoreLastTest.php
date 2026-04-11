<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Performance;

use PHPUnit\Framework\TestCase;
use RuntimeShield\Core\Performance\MetricsStore;
use RuntimeShield\DTO\Performance\MiddlewareMetrics;

final class MetricsStoreLastTest extends TestCase
{
    public function test_last_returns_null_when_empty(): void
    {
        $this->assertNull((new MetricsStore())->last());
    }

    public function test_last_returns_most_recently_pushed_record(): void
    {
        $store = new MetricsStore();
        $store->push($this->make(1.0));
        $store->push($this->make(2.0));
        $store->push($this->make(3.0));

        $last = $store->last();
        $this->assertNotNull($last);
        $this->assertSame(3.0, $last->processingMs);
    }

    public function test_last_returns_null_after_flush(): void
    {
        $store = new MetricsStore();
        $store->push($this->make(1.0));
        $store->flush();
        $this->assertNull($store->last());
    }
    private function make(float $ms): MiddlewareMetrics
    {
        return new MiddlewareMetrics($ms, 0, true, 0, new \DateTimeImmutable());
    }
}
