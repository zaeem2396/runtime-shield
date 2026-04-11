<?php

declare(strict_types=1);

namespace RuntimeShield\Core\Performance;

/**
 * Lightweight nanosecond-precision timer backed by hrtime().
 *
 * Preferred over microtime() because hrtime() is monotonic and not affected
 * by NTP or system clock adjustments, giving accurate wall-clock measurements
 * for sub-millisecond profiling.
 */
final class PerformanceTimer
{
    private int $startNs = 0;

    private int $stopNs = 0;

    private bool $running = false;

    /** Start (or restart) the timer. */
    public function start(): void
    {
        $this->startNs = hrtime(true);
        $this->stopNs = 0;
        $this->running = true;
    }

    /** Stop the timer and record the elapsed time. */
    public function stop(): void
    {
        $this->stopNs = hrtime(true);
        $this->running = false;
    }

    /** Whether the timer is currently running. */
    public function isRunning(): bool
    {
        return $this->running;
    }

    /**
     * Elapsed milliseconds since start().
     * If the timer is still running, returns the live elapsed time.
     * Returns 0.0 if start() was never called.
     */
    public function elapsedMs(): float
    {
        if ($this->startNs === 0) {
            return 0.0;
        }

        $endNs = $this->running ? hrtime(true) : $this->stopNs;

        return ($endNs - $this->startNs) / 1_000_000.0;
    }

    /**
     * Whether the timer has never been started (both start and stop are 0).
     */
    public function isZero(): bool
    {
        return $this->startNs === 0;
    }

    /**
     * Reset the timer to its initial state.
     */
    public function reset(): void
    {
        $this->startNs = 0;
        $this->stopNs = 0;
        $this->running = false;
    }

    /**
     * Record a lap time without stopping the timer.
     * Returns the elapsed ms since start() was called.
     */
    public function lap(): float
    {
        return $this->elapsedMs();
    }

    /**
     * Execute a callable, time it, and return both the result and elapsed ms.
     *
     * @template T
     *
     * @param callable(): T $fn
     *
     * @return array{result: T, elapsed_ms: float}
     */
    public static function measure(callable $fn): array
    {
        $timer = new self();
        $timer->start();
        $result = $fn();
        $timer->stop();

        return [
            'result' => $result,
            'elapsed_ms' => $timer->elapsedMs(),
        ];
    }
}
