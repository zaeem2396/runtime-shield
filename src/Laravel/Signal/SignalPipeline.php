<?php

declare(strict_types=1);

namespace RuntimeShield\Laravel\Signal;

use Illuminate\Http\Request;
use RuntimeShield\Contracts\SamplerContract;
use RuntimeShield\Contracts\Signal\AuthCollectorContract;
use RuntimeShield\Contracts\Signal\RequestCapturerContract;
use RuntimeShield\Contracts\Signal\ResponseCapturerContract;
use RuntimeShield\Contracts\Signal\RouteCollectorContract;
use RuntimeShield\Contracts\Signal\RuntimeContextStoreContract;
use RuntimeShield\Contracts\Signal\SignalPipelineContract;
use RuntimeShield\Contracts\Signal\SignalStoreContract;
use RuntimeShield\Core\RuntimeContextBuilder;
use RuntimeShield\DTO\SecurityRuntimeContext;
use Symfony\Component\HttpFoundation\Response;

/**
 * Orchestrates two-phase signal collection for a single request lifecycle.
 *
 * Phase 1 (handle): sampling gate → request + route + auth capture.
 * Phase 2 (terminate): response capture → context assembly → context store.
 */
final class SignalPipeline implements SignalPipelineContract
{
    /** Tracks whether the current request passed the sampling gate. */
    private bool $sampling = false;

    public function __construct(
        private readonly SamplerContract $sampler,
        private readonly SignalStoreContract $signalStore,
        private readonly RuntimeContextStoreContract $contextStore,
        private readonly RequestCapturerContract $requestCapturer,
        private readonly ResponseCapturerContract $responseCapturer,
        private readonly RouteCollectorContract $routeCollector,
        private readonly AuthCollectorContract $authCollector,
    ) {
    }

    public function collectRequest(Request $request): void
    {
        if (! $this->sampler->shouldSample()) {
            $this->sampling = false;

            return;
        }

        $this->sampling = true;

        $this->signalStore->storeRequest(
            $this->requestCapturer->capture($request),
        );
    }

    public function assemble(Response $response, float $startTimeMs): SecurityRuntimeContext|null
    {
        if (! $this->sampling) {
            return null;
        }

        return null;
    }

    public function reset(): void
    {
        $this->sampling = false;
        $this->signalStore->reset();
        $this->contextStore->reset();
    }
}
