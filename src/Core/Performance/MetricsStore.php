<?php

declare(strict_types=1);

namespace RuntimeShield\Core\Performance;

use RuntimeShield\DTO\Performance\MiddlewareMetrics;

/**
 * In-memory ring buffer of recent MiddlewareMetrics records.
 *
 * Stores up to $capacity snapshots and exposes aggregate statistics
 * (average processing time, max, min) without requiring a persistence layer.
 */
final class MetricsStore
{
    /** @var list<MiddlewareMetrics> */
    private array $records = [];

    public function __construct(private readonly int $capacity = 100)
    {
    }

    /** Append a new metrics record, evicting the oldest when capacity is reached. */
    public function push(MiddlewareMetrics $metrics): void
    {
        if (count($this->records) >= $this->capacity) {
            array_shift($this->records);
        }

        $this->records[] = $metrics;
    }

    /** @return list<MiddlewareMetrics> */
    public function all(): array
    {
        return $this->records;
    }

    public function count(): int
    {
        return count($this->records);
    }

    /** Average processing time across all stored records, or 0.0 when empty. */
    public function averageMs(): float
    {
        if ($this->records === []) {
            return 0.0;
        }

        $total = array_sum(array_map(
            static fn (MiddlewareMetrics $m): float => $m->processingMs,
            $this->records,
        ));

        return $total / count($this->records);
    }

    /** Maximum processing time across all stored records, or 0.0 when empty. */
    public function maxMs(): float
    {
        if ($this->records === []) {
            return 0.0;
        }

        return max(array_map(
            static fn (MiddlewareMetrics $m): float => $m->processingMs,
            $this->records,
        ));
    }

    /** Minimum processing time across all stored records, or 0.0 when empty. */
    public function minMs(): float
    {
        if ($this->records === []) {
            return 0.0;
        }

        return min(array_map(
            static fn (MiddlewareMetrics $m): float => $m->processingMs,
            $this->records,
        ));
    }

    /** Fraction of records where the request was sampled. */
    public function samplingRate(): float
    {
        if ($this->records === []) {
            return 0.0;
        }

        $sampled = count(array_filter(
            $this->records,
            static fn (MiddlewareMetrics $m): bool => $m->wasSampled,
        ));

        return $sampled / count($this->records);
    }

    public function flush(): void
    {
        $this->records = [];
    }

    /**
     * Aggregate statistics as a plain array — useful for JSON output or
     * the runtime-shield:bench summary.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'count'         => $this->count(),
            'avg_ms'        => round($this->averageMs(), 4),
            'max_ms'        => round($this->maxMs(), 4),
            'min_ms'        => round($this->minMs(), 4),
            'sampling_rate' => round($this->samplingRate(), 4),
        ];
    }
}
