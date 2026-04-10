<?php

declare(strict_types=1);

namespace RuntimeShield\Contracts\Signal;

use Illuminate\Http\Request;
use RuntimeShield\DTO\SecurityRuntimeContext;
use Symfony\Component\HttpFoundation\Response;

/**
 * Orchestrates the two-phase signal collection and context assembly pipeline.
 *
 * Phase 1 — collectRequest(): called in middleware handle(); captures the
 *   inbound request, route, and auth signals.
 *
 * Phase 2 — assemble(): called in middleware terminate(); captures the
 *   response signal and assembles the full SecurityRuntimeContext.
 *   Returns null when the request was not sampled.
 */
interface SignalPipelineContract
{
    public function collectRequest(Request $request): void;

    public function assemble(Response $response, float $startTimeMs): SecurityRuntimeContext|null;

    public function reset(): void;
}
