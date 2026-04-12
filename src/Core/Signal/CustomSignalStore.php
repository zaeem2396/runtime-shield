<?php

declare(strict_types=1);

namespace RuntimeShield\Core\Signal;

/**
 * In-memory store for custom signal data collected by user-defined
 * CustomSignalCollectorContract implementations.
 *
 * Data is keyed by the collector's ID and persists for the duration of the
 * request lifecycle. Call flush() in the pipeline reset phase to clear data
 * between requests in long-running processes (e.g. Octane).
 */
final class CustomSignalStore
{
    /** @var array<string, array<string, mixed>> */
    private array $data = [];

    /**
     * Store the signal data for the given collector ID.
     *
     * @param array<string, mixed> $signals
     */
    public function store(string $id, array $signals): void
    {
        $this->data[$id] = $signals;
    }

    /**
     * Retrieve the signal data for a specific collector ID.
     * Returns null when no data has been stored for that ID.
     *
     * @return array<string, mixed>|null
     */
    public function get(string $id): array|null
    {
        return $this->data[$id] ?? null;
    }

    /**
     * Return all stored custom signal data keyed by collector ID.
     *
     * @return array<string, array<string, mixed>>
     */
    public function all(): array
    {
        return $this->data;
    }

    /**
     * Check whether data has been stored for the given collector ID.
     */
    public function has(string $id): bool
    {
        return isset($this->data[$id]);
    }

    /**
     * Return the number of collectors whose data has been stored.
     */
    public function count(): int
    {
        return count($this->data);
    }

    /**
     * Clear all stored custom signal data.
     * Should be called during pipeline reset to prevent cross-request leakage.
     */
    public function flush(): void
    {
        $this->data = [];
    }
}
