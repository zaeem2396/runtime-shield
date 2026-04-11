<?php

declare(strict_types=1);

namespace RuntimeShield\Core\Performance;

use Illuminate\Http\Request;
use RuntimeShield\Contracts\Signal\SignalPipelineContract;
use RuntimeShield\DTO\SecurityRuntimeContext;
use Symfony\Component\HttpFoundation\Response;

/**
 * No-op SignalPipelineContract implementation used when RuntimeShield is
 * globally disabled (runtime_shield.enabled = false).
 *
 * All methods return immediately without any allocation, signal capture,
 * or context assembly — delivering absolute zero overhead on the hot path.
 */
final class NullSignalPipeline implements SignalPipelineContract
{
    public function collectRequest(Request $request): void
    {
        // intentional no-op
    }

    public function assemble(Response $response, float $startTimeMs): SecurityRuntimeContext|null
    {
        return null;
    }

    public function reset(): void
    {
        // intentional no-op
    }
}
