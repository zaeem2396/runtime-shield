<?php

declare(strict_types=1);

namespace RuntimeShield\Core;

use DateTimeImmutable;
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

    public function withRequest(RequestSignal $signal): static
    {
        $clone          = clone $this;
        $clone->request = $signal;

        return $clone;
    }
}
