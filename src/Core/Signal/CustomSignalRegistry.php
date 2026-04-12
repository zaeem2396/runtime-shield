<?php

declare(strict_types=1);

namespace RuntimeShield\Core\Signal;

use RuntimeShield\Contracts\Signal\CustomSignalCollectorContract;

/**
 * Mutable registry of user-defined custom signal collectors.
 *
 * Bound as a singleton in the service container so collectors can be
 * registered during application boot and remain available for the full
 * lifetime of the process.
 *
 * The SignalPipeline resolves this registry and iterates all registered
 * collectors during Phase 1 (handle phase) of each request.
 */
final class CustomSignalRegistry
{
    /** @var list<CustomSignalCollectorContract> */
    private array $collectors = [];

    public function register(CustomSignalCollectorContract $collector): void
    {
        $this->collectors[] = $collector;
    }

    /**
     * Return all registered collectors.
     *
     * @return list<CustomSignalCollectorContract>
     */
    public function all(): array
    {
        return $this->collectors;
    }

    public function count(): int
    {
        return count($this->collectors);
    }

    public function has(string $id): bool
    {
        foreach ($this->collectors as $collector) {
            if ($collector->id() === $id) {
                return true;
            }
        }

        return false;
    }

    public function find(string $id): CustomSignalCollectorContract|null
    {
        foreach ($this->collectors as $collector) {
            if ($collector->id() === $id) {
                return $collector;
            }
        }

        return null;
    }

    /**
     * Remove the collector with the given ID.
     * Returns true when a collector was found and removed.
     */
    public function unregister(string $id): bool
    {
        foreach ($this->collectors as $index => $collector) {
            if ($collector->id() === $id) {
                array_splice($this->collectors, $index, 1);

                return true;
            }
        }

        return false;
    }
}
