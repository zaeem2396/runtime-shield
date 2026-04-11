<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Performance;

use PHPUnit\Framework\TestCase;
use RuntimeShield\Core\Performance\PerformanceTimer;

final class PerformanceTimerTest extends TestCase
{
    public function test_elapsed_ms_returns_0_before_start(): void
    {
        $timer = new PerformanceTimer();
        $this->assertSame(0.0, $timer->elapsedMs());
    }

    public function test_is_running_false_before_start(): void
    {
        $timer = new PerformanceTimer();
        $this->assertFalse($timer->isRunning());
    }

    public function test_is_running_true_after_start(): void
    {
        $timer = new PerformanceTimer();
        $timer->start();
        $this->assertTrue($timer->isRunning());
    }

    public function test_is_running_false_after_stop(): void
    {
        $timer = new PerformanceTimer();
        $timer->start();
        $timer->stop();
        $this->assertFalse($timer->isRunning());
    }

    public function test_elapsed_ms_is_positive_after_start_and_stop(): void
    {
        $timer = new PerformanceTimer();
        $timer->start();
        usleep(1000); // 1 ms
        $timer->stop();

        $this->assertGreaterThan(0.0, $timer->elapsedMs());
    }

    public function test_elapsed_ms_increases_while_running(): void
    {
        $timer = new PerformanceTimer();
        $timer->start();
        $first = $timer->elapsedMs();
        usleep(500);
        $second = $timer->elapsedMs();

        $this->assertGreaterThanOrEqual($first, $second);
    }

    public function test_reset_clears_state(): void
    {
        $timer = new PerformanceTimer();
        $timer->start();
        $timer->stop();
        $timer->reset();

        $this->assertFalse($timer->isRunning());
        $this->assertSame(0.0, $timer->elapsedMs());
    }

    public function test_lap_returns_positive_while_running(): void
    {
        $timer = new PerformanceTimer();
        $timer->start();
        usleep(500);

        $this->assertGreaterThan(0.0, $timer->lap());
        $this->assertTrue($timer->isRunning()); // still running after lap
    }

    public function test_measure_returns_result_and_elapsed(): void
    {
        $measured = PerformanceTimer::measure(static fn () => 42);

        $this->assertSame(42, $measured['result']);
        $this->assertArrayHasKey('elapsed_ms', $measured);
        $this->assertIsFloat($measured['elapsed_ms']);
        $this->assertGreaterThanOrEqual(0.0, $measured['elapsed_ms']);
    }

    public function test_measure_times_callable_duration(): void
    {
        $measured = PerformanceTimer::measure(static function (): string {
            usleep(2000); // ~2 ms

            return 'done';
        });

        $this->assertSame('done', $measured['result']);
        $this->assertGreaterThan(0.0, $measured['elapsed_ms']);
    }
}
