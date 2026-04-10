<?php

declare(strict_types=1);

namespace RuntimeShield\Core;

use RuntimeShield\Contracts\RuntimeContextBuilderContract;
use RuntimeShield\DTO\SecurityRuntimeContext;
use RuntimeShield\DTO\Signal\AuthSignal;
use RuntimeShield\DTO\Signal\RequestSignal;
use RuntimeShield\DTO\Signal\ResponseSignal;
use RuntimeShield\DTO\Signal\RouteSignal;

/**
 * Fluent builder for SecurityRuntimeContext.
 *
 * Accumulates signal references and metadata, then produces an immutable
 * SecurityRuntimeContext via build(). Each call to build() generates a fresh
 * requestId when none was explicitly provided via withRequestId().
 */
final class RuntimeContextBuilder implements RuntimeContextBuilderContract
{
    private RequestSignal|null $request = null;

    private ResponseSignal|null $response = null;

    private RouteSignal|null $route = null;

    private AuthSignal|null $auth = null;

    private string|null $requestId = null;

    private float $processingTimeMs = 0.0;

    public function withRequest(RequestSignal $signal): static
    {
        $clone = clone $this;
        $clone->request = $signal;

        return $clone;
    }

    public function withResponse(ResponseSignal $signal): static
    {
        $clone = clone $this;
        $clone->response = $signal;

        return $clone;
    }

    public function withRoute(RouteSignal $signal): static
    {
        $clone = clone $this;
        $clone->route = $signal;

        return $clone;
    }

    public function withAuth(AuthSignal $signal): static
    {
        $clone = clone $this;
        $clone->auth = $signal;

        return $clone;
    }

    public function withRequestId(string $id): static
    {
        $clone = clone $this;
        $clone->requestId = $id;

        return $clone;
    }

    public function withProcessingTimeMs(float $ms): static
    {
        $clone = clone $this;
        $clone->processingTimeMs = $ms;

        return $clone;
    }

    /**
     * Produce the immutable SecurityRuntimeContext.
     * If no requestId was set, a cryptographically random hex string is generated.
     */
    public function build(): SecurityRuntimeContext
    {
        return new SecurityRuntimeContext(
            requestId:       $this->requestId ?? bin2hex(random_bytes(8)),
            createdAt:       new \DateTimeImmutable(),
            processingTimeMs: $this->processingTimeMs,
            request:         $this->request,
            response:        $this->response,
            route:           $this->route,
            auth:            $this->auth,
        );
    }
}
