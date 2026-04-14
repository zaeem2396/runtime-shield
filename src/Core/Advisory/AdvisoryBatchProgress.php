<?php

declare(strict_types=1);

namespace RuntimeShield\Core\Advisory;

/**
 * Optional listener for batched AI advisory HTTP calls (e.g. Artisan progress lines).
 */
final class AdvisoryBatchProgress
{
    private \Closure|null $listener = null;

    public function setListener(?\Closure $listener): void
    {
        $this->listener = $listener;
    }

    public function notify(int $currentBatch, int $totalBatches, int $violationsInBatch): void
    {
        if ($this->listener !== null) {
            ($this->listener)($currentBatch, $totalBatches, $violationsInBatch);
        }
    }

    public function clear(): void
    {
        $this->listener = null;
    }
}
