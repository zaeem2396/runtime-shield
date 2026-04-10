<?php

declare(strict_types=1);

namespace RuntimeShield\Contracts;

use RuntimeShield\DTO\SecurityRuntimeContext;
use RuntimeShield\DTO\Signal\AuthSignal;
use RuntimeShield\DTO\Signal\RequestSignal;
use RuntimeShield\DTO\Signal\ResponseSignal;
use RuntimeShield\DTO\Signal\RouteSignal;

/**
 * Fluent builder contract for assembling a SecurityRuntimeContext.
 *
 * All with*() methods return static to allow chaining.
 * build() produces the final immutable context; the builder is single-use.
 */
interface RuntimeContextBuilderContract
{
    public function withRequest(RequestSignal $signal): static;

    public function withResponse(ResponseSignal $signal): static;

    public function withRoute(RouteSignal $signal): static;

    public function withAuth(AuthSignal $signal): static;

    public function withRequestId(string $id): static;

    public function withProcessingTimeMs(float $ms): static;

    public function build(): SecurityRuntimeContext;
}
