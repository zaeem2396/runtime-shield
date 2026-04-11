<?php

declare(strict_types=1);

namespace RuntimeShield\DTO\Performance;

/**
 * Immutable snapshot of RuntimeShield middleware processing overhead
 * for a single request lifecycle.
 *
 * Collected by RuntimeShieldMiddleware and available for inspection by
 * application code, monitoring hooks, or the bench command.
 */
final class MiddlewareMetrics
{
    public function __construct(
        /** Wall-clock time the middleware consumed, in milliseconds. */
        public readonly float $processingMs,
        /** Net memory allocated during processing, in kilobytes (may be negative after GC). */
        public readonly int $memoryDeltaKb,
        /** Whether the request was accepted by the sampler. */
        public readonly bool $wasSampled,
        /** Number of rules that were evaluated (0 when not sampled or async). */
        public readonly int $rulesEvaluated,
        /** Timestamp at which metrics were captured. */
        public readonly \DateTimeImmutable $capturedAt,
    ) {
    }

    /**
     * Whether the processing time is within the given budget.
     *
     * @param float $budgetMs Maximum acceptable processing time in ms (default: 5 ms)
     */
    public function isWithinBudget(float $budgetMs = 5.0): bool
    {
        return $this->processingMs <= $budgetMs;
    }

    /**
     * Human-readable processing time, e.g. "1.2345 ms".
     */
    public function formattedMs(): string
    {
        return round($this->processingMs, 4) . ' ms';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'processing_ms'   => round($this->processingMs, 4),
            'memory_delta_kb' => $this->memoryDeltaKb,
            'was_sampled'     => $this->wasSampled,
            'rules_evaluated' => $this->rulesEvaluated,
            'captured_at'     => $this->capturedAt->format(\DateTimeInterface::ATOM),
        ];
    }
}
