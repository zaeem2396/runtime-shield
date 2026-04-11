<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Performance;

use PHPUnit\Framework\TestCase;
use RuntimeShield\Core\Performance\PerformanceTimer;

final class PerformanceTimerEdgeCaseTest extends TestCase
{
    public function test_start_restarts_timer(): void
    {
        $timer = new PerformanceTimer();
        $timer->start();
        usleep(2000); // 2 ms
        $timer->stop();
        $first = $timer->elapsedMs();

        $timer->start(); // restart immediately — accumulated time resets
        $second = $timer->elapsedMs();

        // Right after restart elapsed should be well below the first measurement
        $this->assertLessThan($first, $second + 0.5);
    }

    public function test_stop_freezes_elapsed(): void
    {
        $timer = new PerformanceTimer();
        $timer->start();
        $timer->stop();
        $atStop = $timer->elapsedMs();
        usleep(1000);
        $afterSleep = $timer->elapsedMs();

        $this->assertSame($atStop, $afterSleep);
    }

    public function test_measure_returns_callable_return_value(): void
    {
        $measured = PerformanceTimer::measure(static fn () => ['a', 'b', 'c']);
        $this->assertSame(['a', 'b', 'c'], $measured['result']);
    }

    public function test_measure_works_with_void_callable(): void
    {
        $measured = PerformanceTimer::measure(static fn (): null => null);

        $this->assertNull($measured['result']);
        $this->assertGreaterThanOrEqual(0.0, $measured['elapsed_ms']);
    }

    public function test_reset_after_start_and_stop(): void
    {
        $timer = new PerformanceTimer();
        $timer->start();
        $timer->stop();
        $timer->reset();

        $this->assertFalse($timer->isRunning());
        $this->assertSame(0.0, $timer->elapsedMs());
    }
}
